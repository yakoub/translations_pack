<?php
namespace Drupal\translations_pack\handlers;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\translations_pack\Form\TranslationForm;

trait HandlerTrait {
  /**
   * copied from ContentTranslationHandler
   * only modifiction is to check : $form_object instanceof TranslationForm
   */
  public function entityFormSharedElements($element, FormStateInterface $form_state, $form) {
    static $ignored_types;

    // @todo Find a more reliable way to determine if a form element concerns a
    //   multilingual value.
    if (!isset($ignored_types)) {
      $ignored_types = array_flip(['actions', 'value', 'hidden', 'vertical_tabs', 'token', 'details', 'link']);
    }

    /** @var \Drupal\Core\Entity\ContentEntityForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_object->getEntity();
    $display_translatability_clue = !$entity->isDefaultTranslationAffectedOnly();
    $hide_untranslatable_fields = $entity->isDefaultTranslationAffectedOnly() && !$entity->isDefaultTranslation();
    $translation_form = $form_state->get(['content_translation', 'translation_form']);
    $display_warning = FALSE;

    // We use field definitions to identify untranslatable field widgets to be
    // hidden. Fields that are not involved in translation changes checks should
    // not be affected by this logic (the "revision_log" field, for instance).
    $field_definitions = array_diff_key($entity->getFieldDefinitions(), array_flip($this->getFieldsToSkipFromTranslationChangesCheck($entity)));

    foreach (Element::children($element) as $key) {
      if (!isset($element[$key]['#type'])) {
        $this->entityFormSharedElements($element[$key], $form_state, $form);
      }
      else {
        // Ignore non-widget form elements.
        if (isset($ignored_types[$element[$key]['#type']])) {
          continue;
        }
        // Elements are considered to be non multilingual by default.
        if (empty($element[$key]['#multilingual'])) {
          // If we are displaying a multilingual entity form we need to provide
          // translatability clues, otherwise the non-multilingual form elements
          // should be hidden.
          if (!$translation_form) {
            if ($display_translatability_clue) {
              $this->addTranslatabilityClue($element[$key]);
            }
            // Hide widgets for untranslatable fields.
            if ($hide_untranslatable_fields && isset($field_definitions[$key])) {
              $element[$key]['#access'] = FALSE;
              $display_warning = TRUE;
            }
          }
          else {
            $element[$key]['#access'] = FALSE;
          }
        }
      }
    }

    // custom check
    if ($form_object instanceof TranslationForm || isset($entity->translations_pack_original)) {
      $display_warning = FALSE;
    }

    if ($display_warning && !$form_state->isSubmitted() && !$form_state->isRebuilding()) {
      $url = $entity->getUntranslated()->toUrl('edit-form')->toString();
      $this->messenger->addWarning($this->t('Fields that apply to all languages are hidden to avoid conflicting changes. <a href=":url">Edit them on the original language form</a>.', [':url' => $url]));
    }

    return $element;
  }
}
