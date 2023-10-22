<?php
namespace Drupal\translations_pack;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\translations_pack\PackConfig;
use Drupal\content_translation\ContentTranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

class TranslationsPackHandler implements TranslationsPackHandlerInterface, EntityHandlerInterface {
  
  use StringTranslationTrait;

  protected EntityTypeInterface $entity_type;

  public function __construct(EntityTypeInterface $entity_type, TranslationInterface $string_translation) {
    $this->entity_type = $entity_type;
    $this->stringTranslation = $string_translation;
  }

  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type,
      $container->get('string_translation')
    );
  }

  protected function adminRoute(RouteCollection $collection) {
    // Inherit admin route status from edit route, if exists.
    $is_admin = FALSE;
    $entity_type_id = $this->entity_type->id();
    $route_name = "entity.$entity_type_id.edit_form";
    if ($edit_route = $collection->get($route_name)) {
      $is_admin = (bool) $edit_route->getOption('_admin_route');
    }
    return $is_admin;
  }

  public function alterCreateRoute(RouteCollection $collection) {
    $is_admin = $this->adminRoute($collection);
    $entity_type_id = $this->entity_type->id();
    $load_latest_revision = ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id);
    $all_enabled = PackConfig::enabled($entity_type_id);
    $original_route = FALSE;

    if (
      $this->entity_type->hasLinkTemplate('drupal:content-translation-add') &&
      $this->entity_type->hasLinkTemplate('add-form')
    ) {
      $original_route = $collection->get("entity.{$entity_type_id}.add_form");
    }
    elseif ($entity_type_id == 'node') {
      $original_route = $collection->get('node.add');
    }

    if ($original_route) {
      if ($all_enabled) {
        $route_single = clone $original_route;
        $add_route = $original_route;
        $route_single->setPath($original_route->getPath() . '/single');
        $collection->add("entity.$entity_type_id.single_add_form", $route_single);
      }
      else {
        $add_route = clone $original_route;
      }

      $defaults = $add_route->getDefaults();
      if (isset($defaults['_entity_form'])) {
        $defaults['form_operation'] = $defaults['_entity_form'];
      }
      else {
        $defaults['form_operation'] = "$entity_type_id.default";
      }
      if (!isset($defaults['entity_type_id'])) {
        $defaults['entity_type_id'] = $entity_type_id;
      }
      unset($defaults['_entity_form']);
      $defaults['_controller'] =
        '\Drupal\translations_pack\Controller\TranslationsPackController::build_add';
      $add_route->setDefaults($defaults);
      // already has requirements cloned from `add-form` 
      $add_route->setRequirement('_access_translations_pack_create', $entity_type_id);

      if (!$all_enabled) {
        $add_route->setPath($add_route->getPath() . '/pack');
        $collection->add("entity.$entity_type_id.pack_add_form", $add_route);
      }
    }
  }

  public function alterUpdateRoute(RouteCollection $collection) {
    $entity_type_id = $this->entity_type->id();
    $is_admin = $this->adminRoute($collection);
    $all_enabled = PackConfig::enabled($entity_type_id);
    $load_latest_revision = ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id);
    $edit_route_path = $this->entity_type->getLinkTemplate('edit-form');

    if ($all_enabled) {
      $route_single = clone $collection->get("entity.{$entity_type_id}.edit_form");
      $route_single->setPath($edit_route_path . '/single');
      $collection->remove("entity.{$entity_type_id}.edit_form");
      $collection->add("entity.$entity_type_id.single_edit_form", $route_single);
    }

    $route = new Route($edit_route_path);
    $route->setDefaults(
      [
        '_controller' => 
          '\Drupal\translations_pack\Controller\TranslationsPackController::build_pack',
        '_title' => 'Translations',
        'entity_type_id' => $entity_type_id,
      ]
    );
    $route->setRequirement('_entity_access', "{$entity_type_id}.update");
    $route->setRequirement('_access_content_translation_overview', $entity_type_id);
    $route->setRequirement('_access_translations_pack_edit', $entity_type_id);
    $route->setOption('parameters', [
      $entity_type_id => 
        [
          'type' => 'entity:' . $entity_type_id,
          'load_latest_revision' => $load_latest_revision,
        ],
    ]);
    $route->setOption('_admin_route', $is_admin);
    if ($all_enabled) {
      $collection->add("entity.{$entity_type_id}.edit_form", $route);
    }
    else {
      $route->setPath($edit_route_path . '/pack');
      $collection->add("entity.{$entity_type_id}.pack_edit_form", $route);
    }
  }

  public function deriveLocalTasks(array &$derivatives, $base_plugin_definition) {
    $routes = [];
    $entity_type_id = $this->entity_type->id();
    $all_enabled = PackConfig::enabled($entity_type_id);

    $has_add_link = 
      $this->entity_type->hasLinkTemplate('drupal:content-translation-add') &&
      $this->entity_type->hasLinkTemplate('add-form');

    if ($has_add_link || $entity_type_id == 'node') {
      if ($all_enabled) {
        $pack_name = "entity.$entity_type_id.single_add_form";
        $base_title = $this->t('Translations');
        $pack_title = $this->t('Single');
      }
      else {
        $pack_name = "entity.$entity_type_id.pack_add_form";
        $base_title = $this->t('Single');
        $pack_title = $this->t('Translations');
      }

      if ($entity_type_id == 'node') {
        $base_name = "node.add";
      }
      else {
        $base_name = "entity.$entity_type_id.add_form";
      }
      $parent = 'base_route';

      $derivatives[$base_name] = [
        'entity_type' => $entity_type_id,
        'title' => $base_title,
        'route_name' => $base_name,
        $parent => $base_name,
      ] + $base_plugin_definition;

      $derivatives[$pack_name] = [
        'entity_type' => $entity_type_id,
        'title' => $pack_title,
        'route_name' => $pack_name,
        $parent => $base_name,
      ] + $base_plugin_definition;
    }

    if ($this->entity_type->hasLinkTemplate('drupal:content-translation-edit')) {
      if ($all_enabled) {
        $pack_name = "entity.$entity_type_id.single_edit_form";
        $base_title = $this->t('Translations');
        $pack_title = $this->t('Single');
      }
      else {
        $pack_name = "entity.$entity_type_id.pack_edit_form";
        $base_title = $this->t('Single');
        $pack_title = $this->t('Translations');
      }
      $base_name = "entity.$entity_type_id.edit_form";
      $parent = 'parent_id';

      $derivatives[$base_name] = [
        'entity_type' => $entity_type_id,
        'title' => $base_title,
        'route_name' => $base_name,
        $parent => $base_name,
      ] + $base_plugin_definition;

      $derivatives[$pack_name] = [
        'entity_type' => $entity_type_id,
        'title' => $pack_title,
        'route_name' => $pack_name,
        $parent => $base_name,
      ] + $base_plugin_definition;
    }
  }
}
