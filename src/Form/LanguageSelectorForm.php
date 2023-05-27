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
      '#theme' => 'item_list',
      '#wrapper_attributes' => ['class' => ['translations-tabs']],
      '#items' => [],
    ];

    $has_translation[$first] = true;
    foreach ($active_languages as $code => $language) {
      $activated = isset($has_translation[$code]);
      $item = [
        '#plain_text' => $language->getName(),
        '#wrapper_attributes' => [
          'data-code' => $code,
          'data-state' => $activated ? 'on' : 'off',
          'class' => ['language-tab']
        ],
      ];

      $form['tabs']['#items'][$code] = $item;
      $options[$code] = $code;
      if (isset($has_translation[$code])) {
        $default[] = $code;
      }
    }

    $form['tabs']['#items'][$first]['#wrapper_attributes']['class'][] = 'active';

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
