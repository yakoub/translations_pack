<?php

namespace Drupal\translations_inline_pack\Plugin\Field\FieldWidget;

use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;
use Drupal\translations_inline_pack\Element\InlinePackForm;

use Drupal\Core\Render\Element;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Complex inline widget.
 *
 * @FieldWidget(
 *   id = "inline_pack_form_complex",
 *   label = @Translation("Inline pack form - Complex"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions",
 *   },
 *   multiple_values = true
 * )
 */
class InlinePackFormComplex extends InlineEntityFormComplex {
  
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $field_widget = parent::form($items, $form, $form_state, $get_delta);
    $field_widget['#multilingual'] = TRUE;
    return $field_widget;
  }

  protected function getInlineEntityForm($operation, $bundle, $langcode, $delta, array $parents, EntityInterface $entity = NULL) {
    if ($entity) {
      $revisionable = $entity->getEntityType()->isRevisionable();
      if (!$entity->isNew() && $revisionable && !$entity->isLatestRevision()) {
        $entity_storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
        $revision_id = $entity_storage->getLatestRevisionId($entity->id());
        $entity = $entity_storage->loadRevision($revision_id);
      }
    }
    $element = parent::getInlineEntityForm($operation, $bundle, $langcode, $delta, $parents, $entity);
    $element['#type'] = 'inline_pack_form';
    return $element;
  }

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element =  parent::formElement($items, $delta, $element, $form, $form_state);
    foreach (Element::children($element['entities']) as $key) {
      if (isset($element['entities'][$key]['form']['inline_entity_form'])) {
        $inline_form = &$element['entities'][$key]['form']['inline_entity_form'];
        $inline_form['#process'][0][0] = InlinePackForm::class;
      }
    }
    return $element;
   }
}
