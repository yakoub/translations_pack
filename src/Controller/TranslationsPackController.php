<?php

namespace Drupal\translations_pack\Controller;

use Drupal\content_translation\Controller\ContentTranslationController;
use Drupal\translations_pack\TranslationsFormBuilder;
use Drupal\translations_pack\MockRouteMatch;
use Drupal\translations_pack\Form\LanguageSelectorForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Form\FormState;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Render\Element;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\NodeType;

class TranslationsPackController extends ContentTranslationController {
  protected $original_lang;
  protected $source_lang;
  // succesful submit will not rebuild language form and so it won't be "active"
  protected array $active_languages = [];
  protected array $has_translation = [];
  protected array $language_selection = [];
  protected array $tab_error = [];
  protected $entity;

  public function build_add_node(NodeType $node_type, Request $request) {
    $values = ['type' => $node_type->id()];
    $node = $this->entityTypeManager()->getStorage('node')->create($values);
    $language = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    $node->set('langcode', $language->getId());
    $route_match = new MockRouteMatch($node);
    return $this->build_pack('node', $request, $route_match);
  }

  public function build_add($form_operation, Request $request, RouteMatchInterface $route_match) {
    [$entity_type_id, $operation] = explode('.', $form_operation);
    $form_object = $this->entityTypeManager()->getFormObject($entity_type_id, $operation);
    $entity = $form_object->getEntityFromRouteMatch($route_match, $entity_type_id);
    $language = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    $langcode_key = $entity->getEntityType()->getKey('langcode');
    $entity->set($langcode_key, $language->getId());
    $route_match = new MockRouteMatch($entity);
    return $this->build_pack($entity_type_id, $request, $route_match);
  }

  protected function getRequestEntity(RouteMatchInterface $route_match, $entity_type_id) {
    if ($this->entity) {
      return $this->entity;
    }
    $entity = $route_match->getParameter($entity_type_id);
    $entity_storage = $this->entityTypeManager()->getStorage($entity_type_id);
    if (!$entity->isNew() && $entity->getEntityType()->isRevisionable()) {
      $revision_id = $entity_storage->getLatestRevisionId($entity->id());
      $entity = $entity_storage->loadRevision($revision_id);
      $current_language = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
      if ($entity->language()->getId() != $current_language->getId()) {
        $entity = $entity->getTranslation($current_language->getId());
      }
    }
    $this->entity = $entity;
    return $entity;
  }

  protected function getOriginalForm($entity) {
    return $this->entityFormBuilder()->getForm($entity, 'edit');
  }

  public function build($entity_type_id, Request $request, RouteMatchInterface $route_match) {
    $build = [];

    $entity = $this->getRequestEntity($route_match, $entity_type_id);

    $this->original_lang = $entity->language();
    $this->source_lang = $entity->getUntranslated()->language();

    $response_exception = NULL;
    $is_ajax = $request->request->has('_drupal_ajax');
    $active_langcode = '';
    if ($is_ajax && $request->request->has('translations_pack_active_id')) {
      $active_langcode = $request->request->get('translations_pack_active_id');
    }

    $original_completed = FALSE;
    if ($request->isMethod('POST')) {
      if ($request->request->has('language_selection')) {
        $this->language_selection = $request->request->get('language_selection');
      }
      if (!$request->request->has('form_id')) {
        $original_completed = TRUE;
      }
    }

    /*
     * forms may rebuild while original has finished submitting .
     * if not ajax ignore active langcode,
     * if ajax then active lang code is set for other forms only .
     */
    if (!$original_completed && (!$is_ajax || !$active_langcode)) {
      try {
        $this->active_languages[$this->original_lang->getId()] = $this->original_lang; 
        $build['original'] = $this->getOriginalForm($entity);
        $build['original']['#attributes']['data-lang-code'] = $this->original_lang->getId();
      }
      catch (EnforcedResponseException $e) {
        $response_exception = $e;
        $form_states = $this->entityFormBuilder()->getFormStates();
        $langcode = $this->original_lang->getId();
        $entity = $form_states[$langcode][1]->getValue('translations_pack_entity');
        $entity->packed_newid = $form_states[$langcode][1]->getValue('translations_pack_newid');

        // get a fresh copy
        $request->request->set('form_id', '');
        $build['original'] =
          $this->entityFormBuilder()->getForm($entity, 'edit');
        $build['original']['#attributes']['data-lang-code'] = $this->original_lang->getId();
      }
    }

    $this->entityFormBuilder()->setTranslationMode();
    foreach ($this->languageManager()->getLanguages() as $lang_code => $language) {
      if ($lang_code == $this->original_lang->getId()) {
        continue;
      }
      if ($is_ajax && $active_langcode != $lang_code) {
        continue;
      }
      if ($request->isMethod('POST')) {
        if (isset($this->language_selection[$lang_code])) {
          $this->preparePostData($request, $lang_code, $entity);
        }
        else if (!$is_ajax) {
          // language selection not included in ajax
          continue;
        }
      }
      try {
        if ($entity->hasTranslation($lang_code)) {
          $this->has_translation[$lang_code] = true;
          $translation = $entity->getTranslation($lang_code);
          $route_match = new MockRouteMatch($translation);
          $translation_form =
            $this->edit($language, $route_match, $entity->getEntityTypeId());
        }
        else {
          $entity->pack_language_code = $language->getId();
          $route_match = new MockRouteMatch($entity);
          $translation_form =
            $this->add($this->source_lang, $language, $route_match, $entity->getEntityTypeId());
          unset($entity->pack_language_code);
        }
      }
      catch (EnforcedResponseException $e) {
        if (!$response_exception) {
          $response_exception = $e;
        }
        if (!$this->original_lang) {
          $this->original_lang = $language;
        }
        continue;
      }
      $build[$lang_code] = $translation_form;
      $this->active_languages[$lang_code] = $language; 
    }
    
    if ($request->isMethod('POST') && !$is_ajax) {
      $success = $this->saveTranslations($entity);
      if ($success and $response_exception) {
        throw $response_exception; 
      }
    }

    if (isset($build['original'])) {
      $build['original']['translations_pack_active_id'] = [
        '#type' => 'hidden',
        '#attributes' => ['name' => 'translations_pack_active_id'],
      ];
    }
    return $build;
  }

  public function build_pack($entity_type_id, Request $request, RouteMatchInterface $route_match) {

    $build = $this->build($entity_type_id, $request, $route_match);
    $selector_form = new LanguageSelectorForm();
    $language_selection = $this->language_selection ? $this->language_selection : $this->has_translation;
    $build['language_selector'] = $this->formBuilder()
      ->getForm($selector_form, $language_selection, $this->active_languages);

    $build['original']['tabs'] = $build['language_selector']['tabs'];
    $build['original']['tabs']['#weight'] = -1;
    unset($build['language_selector']['tabs']);

    foreach ($this->languageManager()->getLanguages() as $lang_code => $language) {
      if (!isset($build[$lang_code])) {
        continue;
      }
      if ($lang_code == $this->original_lang->getId()) {
        continue;
      }

      $this->integrateTranslationForm($build['original'], $build[$lang_code], $lang_code);
    }
    $this->markTabErrors($build['original']['tabs']);
    $build['#attached'] = [
      'library' => ['translations_pack/tabs']
    ];
    $entity = $this->getRequestEntity($route_match, $entity_type_id);
    $this->moduleHandler()->alter('translations_pack', $build, $entity);
    $themeManager = \Drupal::theme();
    $themeManager->alter('translations_pack', $build, $entity);

    return $build;
  }

  function preparePostData(Request $request, $lang_code, ContentEntityInterface $entity) {
    $postdata = $request->request;
    foreach (['form_id', 'form_build_id', 'form_token'] as $name) {
      $postdata->set($name, $postdata->get("{$name}_{$lang_code}"));
    }
    // this is controlled in translations_pack_form_alter
    $postdata->set('op', 'save');
  }

  function saveTranslations(ContentEntityInterface $entity) {
    $type = $entity->getEntityTypeId();
    $entity_storage = $this->entityTypeManager()->getStorage($type);
    if ($entity->isNew()) {
      $langcode = $this->original_lang->getId();
      $form_states = $this->customFormBuilder->getFormStates();
      list($form_object, $form_state) = $form_states[$langcode];
      $entity_pack = $form_object->getEntity();
      if (!$entity_pack->id()) {
        $newid = $form_state->getValue('translations_pack_newid');
        if ($newid) {
          $entity_pack = $entity_storage->load($newid);
        }
        else {
          $this->getLogger('translations_pack')->error('saved id got lost');
          return;
        }
      }
      foreach ($entity_pack->getTranslationLanguages(FALSE) as $langcode => $language) {
        // check the validation fail case
        if ($langcode != $this->original_lang->getId()) {
          $entity_pack->removeTranslation($langcode);
        }
      }
    }
    elseif ($entity->getEntityType()->isRevisionable()) {
      $revision_id = $entity_storage->getLatestRevisionId($entity->id());
      $entity_pack = $entity_storage->loadRevision($revision_id);
    }
    else {
      $entity_pack = $entity;
    }

    // hack to load original moderation_state
    if ($entity_pack->hasField('moderation_state')) {
      $ignore = $entity_pack->moderation_state->value;
    }
    $success = true;
    foreach ($this->customFormBuilder->getFormStates() as $langcode => $form_pair) {
      if ($langcode == $this->original_lang->getId()) {
        continue;
      }
      if (!isset($this->language_selection[$langcode])) {
        continue;
      }
      list($form_object, $form_state) = $form_pair;
      if ($form_state->hasAnyErrors()) {
        $success = false;
        continue;
      }

      $entity = $form_state->saved_entity;
      if (!$entity) {
        $entity = $form_object->getEntity();
      }

      if ($entity_pack->hasTranslation($langcode)) {
        $new_pack = $entity_pack->getTranslation($langcode);
      }
      else {
        $new_pack = $entity_pack->addTranslation($langcode);
      }
      foreach ($entity as $fieldname => $field_items) {
        if ($field_items->getFieldDefinition()->isTranslatable()) {
          $new_pack->set($fieldname, $field_items->getValue());
        }
      }
      $entity_pack = $new_pack;
    }
    if ($success) {
      $entity_pack->save();
      $this->messenger()->addStatus($this->t('translations saved and state'));
    }
    $entity = $entity_pack;
    return $success;
  }

  protected $translatable_names = [];

  protected $control_names = ['form_id', 'form_token', 'form_build_id'];

  function integrateTranslationForm(array &$original, array &$translation_form, $lang_code) {
    if (!$this->translatable_names) {
      $this->setupTranslationPack($original, $translation_form);
    }
    foreach ($this->translatable_names as $key => $array_parents) {
      $element = &NestedArray::getValue($translation_form, $array_parents);
      if (!$element) {
        $this->getLogger('translations_pack')
          ->error('missing field in translation: @key', ['@key' => $key]);
        continue;
      }
      $this->childrenTitleLanguage($element, $lang_code);
      $original_pack = &NestedArray::getValue($original, $array_parents); 
      $element['#attributes']['class'][] = 'field-language-' . $lang_code;
      $element['#attributes']['data-lang-pack'] = $lang_code;
      $original_pack["{$key}_{$lang_code}"] = $element;
      if (!empty($element['#children_errors'])) {
        $this->tab_error[$lang_code] = TRUE;
      }
      unset($original_pack["{$key}_{$lang_code}"]['#groups']);
    }

    foreach ($this->control_names as $key) {
      $translation_form[$key]['#parents'][0] .= '_' . $lang_code;
      $translation_form[$key]['#name'] .= '_' . $lang_code;
      $original["{$key}_{$lang_code}"] = $translation_form[$key];
      unset($translation_form[$key]);
    }

    foreach (Element::children($translation_form) as $key) {
      unset($translation_form[$key]);
    }
  }


  function setupTranslationPack(array &$original, array &$translation_form) {
    foreach (Element::children($translation_form) as $key) {
      if (isset($translation_form[$key]['#groups'])
        && isset($translation_form[$key]['#groups'][$key])) {
        // this is a group
        continue;
      }
      if (isset($translation_form[$key]['#access'])
        && !($translation_form[$key]['#access'])) {
        // "hidden" field
        continue;
      }
      if (empty($translation_form[$key]['#multilingual'])
        || in_array($key, $this->control_names)
        || !isset($original[$key])
        ) {
        continue;
      }
      $element = &$this->findInChildren($original[$key], '#group');
      if (!$element) {
        $element = &$original[$key];
      }

      $this->translatable_names[$key] = $element['#array_parents'];
      $this->setupElementPack($element, $key, $original);
    }
  }

  function setupElementPack(&$element, $key, &$original) {
    $pack = [
      '#type' => 'container',
      '#attributes' => ['class' => ['translation-pack', "field-$key"]],
      '#weight' => $element['#weight'],
    ];
    $original_code = $this->original_lang->getId();
    $this->childrenTitleLanguage($element, $original_code);
    $parents = $element['#array_parents'];
    $element_index = array_pop($parents);
    $parent_element = &NestedArray::getValue($original, $parents);

    if (isset($element['#group'])) {
      $pack['#group'] = $element['#group'];
      $pack['#parents'] = $element['#parents'];
      $pack['#groups'] = &$element['#groups'];
    }

    $element['#attributes']['data-lang-pack'] = 'original';
    $element['#attributes']['class'][] = 'field-language-' . $original_code;
    $pack[$key . '_original'] = $element;
    unset($pack[$key . '_original']['#groups']);
    $pack[$key . '_original']['#attributes']['class'][] 
      = 'active';
    if (isset($pack['#groups'])) {
      $parent = $pack['#group'];
      foreach ($pack['#groups'][$parent] as $index => &$child) {
        if (!is_array($child)) {
          continue;
        }
        $child['#test_reference'] = true;
        if (isset($element['#test_reference'])) {
          $parent_element[$element_index] = $pack;
          $pack['#groups'][$parent][$index] = &$parent_element[$element_index];
        }
        unset($child['#test_reference']);
      }
    }
    else {
      $parent_element[$element_index] = $pack;
    }
  }

  function &findInChildren(&$element, $control, $level = 0) {
    $null = NULL;
    if ($level > 16) {
      $this->getLogger('translations_pack')->error('findInChildren recursion exceeded limit');
      return $null;
    }
    if (isset($element[$control])) {
      return $element;
    }
    $level++;
    foreach (Element::children($element) as $key) {
      $found = &$this->findInChildren($element[$key], $control, $level);
      if ($found) {
        return $found;
      }
    }
    return $null;
  }

  function childrenTitleLanguage(&$element, $lang_code, $level = 0) {
    if ($level > 16) {
      $this->getLogger('translations_pack')
        ->error('childrenTitleLanguage recursion exceeded limit');
      return;
    }
    $level++;
    $language = $this->active_languages[$lang_code];
    if (isset($element['#title'])) {
      $args = ['@title' => $element['#title'], '@language' => $language->getName()];
      $element['#title'] = new FormattableMarkup('@title (@language)', $args);
    }
    foreach (Element::children($element) as $key) {
      $this->childrenTitleLanguage($element[$key], $lang_code, $level);
    }
  }

  function markTabErrors(&$tabs) {
    foreach ($this->tab_error as $langcode => $set) {
      $tab = &$tabs['#rows'][0][$langcode];
      if (isset($tab['class'])) {
        $tab['class'][] = 'has-error';
      }
      else {
        $tab['class'] = ['has-error'];
      }
    }
  }

  protected $customFormBuilder = NULL;

  protected function entityFormBuilder() {
    if (!$this->customFormBuilder) {
      $this->customFormBuilder = new TranslationsFormBuilder(
        $this->entityTypeManager(),
        $this->formBuilder(),
        $this->moduleHandler()
      );
    }
    return $this->customFormBuilder;
  }
}
