<?php

namespace Drupal\translations_inline_pack\Element;

use Drupal\inline_entity_form\Element\InlineEntityForm; 
use Drupal\translations_inline_pack\Form\PackInlineForm;

/**
 * Provides an inline entity form element.
 *
 * @RenderElement("inline_pack_form")
 */
class InlinePackForm extends InlineEntityForm {

  /**
   * Gets the inline form handler for the given entity type.
   *
   * @return \Drupal\inline_entity_form\InlineFormInterface
   *   The inline form handler.
   */
  public static function getInlineFormHandler($entity_type) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type);
    $container = \Drupal::getContainer();
    $inline_form_handler = PackInlineForm::createInstance($container, $entity_type);

    return $inline_form_handler;
  }

}
