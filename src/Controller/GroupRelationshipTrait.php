<?php

namespace Drupal\translations_pack\Controller;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;

trait GroupRelationshipTrait {

  /**
   * The private store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  function privateTempStore() {
   if (!isset($this->privateTempStoreFactory)) {
     $this->privateTempStoreFactory = \Drupal::service('tempstore.private');
   }
   return $this->privateTempStoreFactory;
  }

  /**
   * Provides the relationship creation form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the relationship to.
   * @param string $plugin_id
   *   The group relation to add content with.
   *
   * @return array
   *   A relationship creation form.
   */
  public function createForm(GroupInterface $group, $plugin_id) {
    $group_relation = $group->getGroupType()->getPlugin($plugin_id);
    $group_relation_type = $group_relation->getRelationType();

    $wizard_id = 'group_entity';
    $store = $this->privateTempStore()->get($wizard_id);
    $store_id = $plugin_id . ':' . $group->id();

    // See if the plugin uses a wizard for creating new entities. Also pass this
    // info to the form state.
    $config = $group_relation->getConfiguration();
    $extra['group_wizard'] = $config['use_creation_wizard'];
    $extra['group_wizard_id'] = $wizard_id;

    // Pass the group, plugin ID and store ID to the form state as well.
    $extra['group'] = $group;
    $extra['group_relation'] = $plugin_id;
    $extra['store_id'] = $store_id;

    // See if we are on the second step of the form.
    $step2 = $extra['group_wizard'] && $store->get("$store_id:step") === 2;

    // Grouped entity form, potentially as wizard step 1.
    if (!$step2) {
      // Figure out what entity type the plugin is serving.
      $entity_type_id = $group_relation_type->getEntityTypeId();
      $entity_type = $this->entityTypeManager()->getDefinition($entity_type_id);
      $storage = $this->entityTypeManager()->getStorage($entity_type_id);

      // Only create a new entity if we have nothing stored.
      if (!$entity = $store->get("$store_id:entity")) {
        $values = [];
        if (($key = $entity_type->getKey('bundle')) && ($bundle = $group_relation_type->getEntityBundle())) {
          $values[$key] = $bundle;
        }
        $entity = $storage->create($values);
      }

      // Use the add form handler if available.
      $operation = 'default';
      if ($entity_type->getFormClass('add')) {
        $operation = 'add';
      }
    }
    // Wizard step 2: Group relationship form.
    else {
      $relationship_type_storage = $this->entityTypeManager()->getStorage('group_content_type');
      assert($relationship_type_storage instanceof GroupRelationshipTypeStorageInterface);

      // Create an empty relationship entity.
      $values = [
        'type' => $relationship_type_storage->getRelationshipTypeId($group->bundle(), $plugin_id),
        'gid' => $group->id(),
      ];
      $entity = $this->entityTypeManager()->getStorage('group_content')->create($values);

      // Group relationship entities have an add form handler.
      $operation = 'add';
    }

    // only addition to original group code
    $this->entity = $entity;
    // Return the entity form with the configuration gathered above.
    return $this->entityFormBuilder()->getForm($entity, $operation, $extra);
  }

}
