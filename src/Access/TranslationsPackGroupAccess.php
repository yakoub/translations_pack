<?php

namespace Drupal\translations_pack\Access;

use Drupal\content_translation\Access\ContentTranslationOverviewAccess;
use Drupal\translations_pack\PackConfig;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\translations_pack\MockRouteMatch;
use Drupal\Core\Access\AccessResult;


/**
 * Access check for translation pack create.
 */
class TranslationsPackGroupAccess extends ContentTranslationOverviewAccess {

  public function access(RouteMatchInterface $route_match, AccountInterface $account, $plugin_id) {

    $group = $route_match->getParameter('group');
    $plugin_id = $route_match->getParameter('plugin_id');

    // We can only get the relationship type ID if the plugin is installed.
    if (!$group->getGroupType()->hasPlugin($plugin_id)) {
      return AccessResult::neutral();
    }

    $group_relation = $group->getGroupType()->getPlugin($plugin_id);
    $group_relation_type = $group_relation->getRelationType();
    $entity_type_id = $group_relation_type->getEntityTypeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    $values = [];
    if (
      ($key = $entity_type->getKey('bundle')) &&
      ($bundle = $group_relation_type->getEntityBundle())
    ) {
      $values[$key] = $bundle;
    }
    $entity = $storage->create($values);
    if (!PackConfig::enabled($entity_type_id, $entity->bundle())) {
      return AccessResult::forbidden('disabled by config');
    }

    $route_match = new MockRouteMatch($entity);
    return parent::access($route_match, $account, $entity_type_id);
  }

}
