<?php
namespace Drupal\translations_pack\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\translations_pack\TranslationsFormState;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityUntranslatableFieldsConstraint;
use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraint;

/**
 * Form handler for the node edit forms.
 *
 * @internal
 */
class TranslationForm extends ContentEntityForm {

  public function getFormId() {
    $form_id = parent::getFormId();
    $entity = $this->getEntity();
    if (isset($entity->pack_language_code)) {
      $form_id .= '_' . $entity->pack_language_code;
    }
    else {
      $form_id .= '_' . $entity->language()->getId();
    }
    return $form_id;
  }

  public function getBaseFormId() {
    $base_form_id = parent::getBaseFormId();
    $base_form_id .= '_' . $this->getEntity()->language()->getId();
    return $base_form_id;
  }

  public function form(array $form, FormStateInterface $form_state) {
    $langcode = $this->getFormLangcode($form_state);
    $type = $this->getEntity()->getEntityTypeId();
    // use += to merge
    $root = "{$type}_{$langcode}";
    $form['#attributes']['data-lang-root'] = $root;
    $form['#parents'] = [$root];
    $form['#tree'] = true;
    $form = parent::form($form, $form_state);

    $form['#entity_builders'][] = [$this, 'contentTranslationEntityBuild'];
    $entity = $this->getEntity();


    $packed_newid = $entity->packed_newid ?? $form_state->getValue('translations_pack_newid');
    $form['translations_pack_newid'] = [
      '#type' => 'hidden',
      '#default_value' => $packed_newid,
      '#parents' => ['translations_pack_newid'],
    ];
    if ($entity->isNew()) {
      foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
        if (isset($form[$field_name])) {
          if ($definition->isTranslatable()) {
            $form[$field_name]['#multilingual'] = TRUE;
          }
          else {
            unset($form[$field_name]);
          }
        }
      }
    }
    return $form;
  }

  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    if (!$form_state instanceof TranslationsFormState) {
      $this->getLogger('translations_pack')->debug('wrong FormState implementation');
      return;
    }
    $extracted = $this
      ->getFormDisplay($form_state)
      ->extractFormValues($entity, $form, $form_state);
    foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
      if (!$definition->isTranslatable() && isset($extracted[$field_name])) {
        $value = $form_state->original_entity->get($field_name)->getValue();
        $entity->set($field_name, $value);
      }
    }
  }

  function contentTranslationEntityBuild($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    if (!$entity->hasField('uid')) {
      return;
    }
    $user_ref = $entity->uid->getValue();
    $translation = ['uid' => $user_ref[0]['target_id']];
    $form_state->setValue('content_translation', $translation);
  }

  protected function flagViolations(EntityConstraintViolationListInterface $violations,
    array $form, FormStateInterface $form_state) {
    $found = [];
    foreach ($violations as $offset => $violation) {
      if ($violation->getConstraint() instanceof EntityChangedConstraint) {
        $this->getLogger('translations_pack')->debug('entity changed violation');
        $found[] = $offset;
      }
      if ($violation->getConstraint() instanceof EntityUntranslatableFieldsConstraint) {
        $this->getLogger('translations_pack')->debug('untranslatable violation');
        $found[] = $offset;
      }
    }
    foreach ($found as $offset) {
      $violations->remove($offset);
    }
    parent::flagViolations($violations, $form, $form_state);
    $values = $form_state->getValues();

    /* test error reporting
    foreach (['fr', 'de'] as $code) {
      $root = 'node_' . $code;
      if (isset($values[$root])) {
        $title_value = $values[$root]['title'][0]['value'];
        if (str_ends_with($title_value, 'y')) {
          $form_state->setErrorByName($root . '][title', 'my error');
        }
      }
    }*/
  }

  public function save(array $form, FormStateInterface $form_state) {
    $form_state->saved_entity = $this->entity;
  }
}
