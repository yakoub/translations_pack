<?php

namespace Drupal\translations_pack\Controller;

use Drupal\translations_pack\TranslationsFormBuilder;
use Drupal\translations_pack\MockRouteMatch;
use Drupal\content_translation\Controller\ContentTranslationController;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormState;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Render\Element;

class TranslationsPackController extends ContentTranslationController {

  public function build_node(ContentEntityInterface $node, Request $request) {
    return $this->build($node, $request);
  }
  public function build_rating(ContentEntityInterface $rating, Request $request) {
    return $this->build($rating, $request);
  }

  protected $original_lang;

  public function build(ContentEntityInterface $entity, Request $request) {
    $build = [];
    $route_match = new MockRouteMatch($entity);
    try {
      $build['original'] =
        $this->entityFormBuilder()->getForm($entity, 'edit');
    }
    catch (EnforcedResponseException $e) {
      // ignore redirect
    }

    $this->original_lang = $entity->language();
    $this->switchFormBuilder(true);
    foreach ($this->languageManager()->getLanguages() as $lang_code => $language) {
      if ($lang_code == $this->original_lang->getId()) {
        continue;
      }
      if ($request->isMethod('POST')) {
        $entity = $this->preparePostData($request, $lang_code, $entity);
      }
      if ($entity->hasTranslation($lang_code)) {
        $translation = $entity->getTranslation($lang_code);
        $route_match = new MockRouteMatch($translation);
        $translation_form =
          $this->edit($language, $route_match, $entity->getEntityTypeId());
      }
      else {
        $route_match = new MockRouteMatch($entity);
        $translation_form =
          $this->add($this->original_lang, $language, $route_match, $entity->getEntityTypeId());
      }
      if (isset($build['original'])) {
        $this->integrateTranslationForm($build['original'], $translation_form, $lang_code);
      }
    }

    return $build;
  }

  function preparePostData(Request $request, $lang_code, ContentEntityInterface $entity) {
    $postdata = $request->request;
    foreach (['form_id', 'form_build_id', 'form_token'] as $name) {
      $postdata->set($name, $postdata->get("{$name}_{$lang_code}"));
    }
    $type = $entity->getEntityTypeId();
    $entity = $this->entityTypeManager()->getStorage($type)->load($entity->id());
    if (method_exists($entity, 'getChangedTime')) {
      $postdata->set("{$type}_{$lang_code}[changed]", $entity->getChangedTime());
    }
    return $entity;
  }

  protected $translatable_names = false;

  protected $control_names = ['form_id', 'form_token', 'form_build_id'];

  function integrateTranslationForm(array &$original, array $translation_form, $lang_code) {
    // only original form actions needed
    unset($translation_form['actions']);
    // not functional with multiple translations on same page
    unset($translation_form['content_translation']);
    unset($translation_form['langcode']);
    
    if (!$this->translatable_names) {
      $this->translatable_names = [];
      foreach (Element::children($translation_form) as $key) {
        if (!empty($translation_form[$key]['#multilingual'])
          && !in_array($key, $this->control_names)) {
          $this->translatable_names[] = $key;
          if ($key == 'uid') {
            dpm(array_keys($translation_form[$key]));
          }
        }
      }
      unset($original['langcode']);
      $this->setupFieldTabs($original);
    }
    foreach ($this->translatable_names as $key) {
      $group = "{$key}_translation_tabs";
      $translation_form[$key]['#attributes']['class'][] 
        = 'field-language-' . $lang_code;
      $original[$group]["{$key}_{$lang_code}"] = $translation_form[$key];
    }
    foreach ($this->control_names as $key) {
      $translation_form[$key]['#parents'][0] .= '_' . $lang_code;
      $translation_form[$key]['#name'] .= '_' . $lang_code;
      $original["{$key}_{$lang_code}"] = $translation_form[$key];
    }
  }

  function setupFieldTabs(array &$original) {
    foreach ($this->translatable_names as $key) {
      $group = "{$key}_translation_tabs";
      $original[$group] = [
        '#type' => 'details',
        '#attributes' => ['class' => ['translation-tabs']],
        '#open' => true,
        '#title' => $group,
        '#weight' => $original[$key]['#weight'],
        '#attached' => [
          'library' => ['translations_pack/tabs']
        ],
      ];
      if (isset($original[$key]['#group'])) {
        $original[$group]['#group'] = $original[$key]['#group'];
        unset($original[$key]['#group']);
      }
      $original[$group]['tabs'] = [
        '#theme' => 'item_list',
        '#items' => $this->languageTabs(),
        '#weight' => $original[$key]['#weight'] - 1,
      ];
      $original[$group][$key . '_original'] = $original[$key];
      $original[$group][$key . '_original']['#attributes']['class'][] 
        = 'field-language-' . $this->original_lang->getId();
      $original[$group][$key . '_original']['#attributes']['class'][] 
        = 'active';
      unset($original[$key]);
    }
  }

  function languageTabs() {
    static $tabs = [];
    if (!$tabs) {
      $tabs[] = [
        '#wrapper_attributes' => [
          'data-code' => $this->original_lang->getId(),
          'class' => ['active'],
        ],
        '#plain_text' => $this->original_lang->getName()
      ];
      foreach ($this->languageManager()->getLanguages() as $language) {
        if ($language->getId() != $this->original_lang->getId()) {
          $tabs[] = [
            '#wrapper_attributes' => ['data-code' => $language->getId()],
            '#plain_text' => $language->getName()
          ];
        }
      }
    }
    return $tabs;
  }

  protected $switch_form_builder = false;

  function switchFormBuilder($set) {
    $this->switch_form_builder = $set;
  }

  protected function entityFormBuilder() {
    if ($this->switch_form_builder) {
      return new TranslationsFormBuilder(
        $this->entityTypeManager(),
        $this->formBuilder(),
        $this->moduleHandler()
      );
    }
    return parent::entityFormBuilder();
  }
}
