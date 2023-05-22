<?php

namespace Drupal\translations_pack\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides a translations pack form.
 */
class LanguageSelectorForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'translations_pack_language_selector';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,
    array $has_translation = [], array $active_languages =[]) {
    $options = [];
    $default = [];
    $first = key($active_languages);

    $form['tabs'] = [
      '#theme' => 'table',
      '#attributes' => ['class' => ['translations-tabs']],
      '#header' => [],
    ];

    $has_translation[$first] = true;
    foreach ($active_languages as $code => $language) {
      $form['tabs']['#header'][] = $language->getName();
      
      $form['tabs']['#rows'][0][$code] = [
        'data' => isset($has_translation[$code]) ? 'Edit' : 'Activate',
        'data-code' => $code,
        'data-state' => isset($has_translation[$code]) ? 'on' : 'off',
      ];
      $options[$code] = $code;
      if (isset($has_translation[$code])) {
        $default[] = $code;
      }
    }

    $form['tabs']['#rows'][0][$first]['class'] = ['active'];

    $form['language_selection'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Languages'),
      '#options' => $options,
      '#default_value' => $default,
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
