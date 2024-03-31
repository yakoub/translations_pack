<?php

namespace Drupal\translations_inline_pack\Form;

use Drupal\inline_entity_form\Form\EntityInlineForm;
use Drupal\translations_inline_pack\ConfigPackStorage;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Render\FormattableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generic pack inline form handler.
 */
class PackInlineForm extends EntityInlineForm {

  /**
   * Gets the form display for the given entity.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The form display.
   */
  protected function getFormDisplay(ContentEntityInterface $entity, $form_mode) {
    $container = \Drupal::getContainer();
    $config_type = \Drupal::entityTypeManager()->getDefinition('entity_form_display');
    $storage = ConfigPackStorage::createInstance($container, $config_type);

    $type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $config_id = "{$type_id}.{$bundle}.{$form_mode}";
    $form_display = $storage->load($config_id);

    return $form_display;
  }

  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);
    $entity = $entity_form['#entity'];
    $langcode_key = $this->entityType->getKey('langcode');
    if (!empty($entity_form['#translating'])) {
      // Hide the non-translatable fields.
      foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
        if (isset($entity_form[$field_name]) && $field_name != $langcode_key) {
          $field_type = $definition->getType();
          if ($field_type == 'entity_reference_revisions' || $field_type == 'entity_reference') {
            $entity_form[$field_name]['#access'] = TRUE;
          }
        }
      }
    }
    $language = $entity->language();
    $this->childrenTitleLanguage($entity_form, $language);
    return $entity_form;
  }

  function childrenTitleLanguage(&$element, $language, $level = 0) {
    if ($level > 16) {
      $this->getLogger('translations_pack')
        ->error('childrenTitleLanguage recursion exceeded limit');
      return;
    }
    $level++;
    if (isset($element['#title'])) {
      $args = ['@title' => $element['#title'], '@language' => $language->getName()];
      $element['#title'] = new FormattableMarkup('@title (@language)', $args);
    }
    foreach (Element::children($element) as $key) {
      $this->childrenTitleLanguage($element[$key], $language, $level);
    }
  }

}
