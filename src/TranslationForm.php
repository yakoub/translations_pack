<?php
namespace Drupal\translations_pack;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;

/**
 * Form handler for the node edit forms.
 *
 * @internal
 */
class TranslationForm extends ContentEntityForm {

  public function getFormId() {
    $form_id = parent::getFormId();
    $form_id .= '_' . $this->getEntity()->language()->getId();
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
    return parent::form($form, $form_state);
  }

  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->disableRedirect();
  }
}
