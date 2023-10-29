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

  const ADD_Controller = '\Drupal\translations_pack\Controller\TranslationsPackController::build_add';

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

  protected function getOriginalAddRoute(RouteCollection $collection) {
    if (
      $this->entity_type->hasLinkTemplate('drupal:content-translation-add') &&
      $this->entity_type->hasLinkTemplate('add-form')
    ) {
      return $collection->get("entity.{$entity_type_id}.add_form");
    }
    return FALSE;
  }

  protected function addCreateAccess(Route $add_route, $entity_type_id) {
    // already has requirements cloned from `add-form` 
    $add_route->setRequirement('_access_translations_pack_create', $entity_type_id);
  }

  protected function configStatus() {
    $entity_type_id = $this->entity_type->id();
    return PackConfig::typeStatus($entity_type_id);
  }

  public function alterCreateRoute(RouteCollection $collection) {
    $is_admin = $this->adminRoute($collection);
    $entity_type_id = $this->entity_type->id();
    $config_status = $this->configStatus();

    $original_route = FALSE;
    if ($config_status == PackConfig::DISABLED) {
      return;
    }

    $original_route = $this->getOriginalAddRoute($collection);
    if (!$original_route) {
      return;
    }

    if ($config_status == PackConfig::ENABLED) {
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
    $defaults['_controller'] = static::ADD_Controller;
    $add_route->setDefaults($defaults);
    $this->addCreateAccess($add_route, $entity_type_id);

    if ($config_status == PackConfig::PARTIAL) {
      $add_route->setPath($add_route->getPath() . '/pack');
      $collection->add("entity.$entity_type_id.pack_add_form", $add_route);
    }
  }

  public function alterUpdateRoute(RouteCollection $collection) {
    $entity_type_id = $this->entity_type->id();
    $is_admin = $this->adminRoute($collection);
    $config_status = $this->configStatus();
    if ($config_status == PackConfig::DISABLED) {
      return;
    }
    $load_latest_revision = ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id);
    $edit_route_path = $this->entity_type->getLinkTemplate('edit-form');

    if ($config_status == PackConfig::ENABLED) {
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
    if ($config_status == PackConfig::ENABLED) {
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
    $config_status = $this->configStatus();
    if ($config_status == PackConfig::DISABLED) {
      return;
    }

    $has_add_link = $this->hasAddLink();

    if ($has_add_link) {
      if ($this->addTabDefault($config_status)) {
        $pack_name = "entity.$entity_type_id.single_add_form";
        $base_title = $this->t('Translations');
        $pack_title = $this->t('Single');
      }
      else {
        $pack_name = "entity.$entity_type_id.pack_add_form";
        $base_title = $this->t('Single');
        $pack_title = $this->t('Translations');
      }
      $config = $this->getAddTasksConfig();
      if (isset($config['pack_name'])) {
        $pack_name = $config['pack_name'];
      }

      $derivatives[$config['base_name']] = [
        'entity_type' => $entity_type_id,
        'title' => $base_title,
        'route_name' => $config['route_name'],
        $config['parent'] => $config['parent_name'],
      ] + $base_plugin_definition;

      $derivatives[$pack_name] = [
        'entity_type' => $entity_type_id,
        'title' => $pack_title,
        'route_name' => $pack_name,
        $config['parent'] => $config['parent_name'],
      ] + $base_plugin_definition;
    }

    if ($this->entity_type->hasLinkTemplate('drupal:content-translation-edit')) {
      if ($config_status == PackConfig::ENABLED) {
        $pack_name = "entity.$entity_type_id.single_edit_form";
        $base_title = $this->t('Translations');
        $pack_title = $this->t('Single');
      }
      else {
        $pack_name = "entity.$entity_type_id.pack_edit_form";
        $base_title = $this->t('Single');
        $pack_title = $this->t('Translations');
      }
      $config = $this->getEditTasksConfig();

      $derivatives[$config['base_name']] = [
        'entity_type' => $entity_type_id,
        'title' => $base_title,
        'route_name' => $config['route_name'],
        $config['parent'] => $config['parent_name'],
      ] + $base_plugin_definition;

      $derivatives[$pack_name] = [
        'entity_type' => $entity_type_id,
        'title' => $pack_title,
        'route_name' => $pack_name,
        $config['parent'] => $config['parent_name'],
      ] + $base_plugin_definition;
    }
  }

  protected function hasAddLink() {
    return
      $this->entity_type->hasLinkTemplate('drupal:content-translation-add') &&
      $this->entity_type->hasLinkTemplate('add-form');
  }

  protected function addTabDefault($config_status) {
    return $config_status == PackConfig::ENABLED;
  }

  protected function getAddTasksConfig() {
    $entity_type_id = $this->entity_type->id();
    return [
      'route_name' => "entity.$entity_type_id.add_form",
      'base_name' => "entity.$entity_type_id.add_form",
      'parent' => 'base_route',
      'parent_name' => "entity.$entity_type_id.add_form", 
    ];
  }

  protected function getEditTasksConfig() {
    $entity_type_id = $this->entity_type->id();
    return [
      'route_name' => "entity.$entity_type_id.edit_form",
      'base_name' => "entity.$entity_type_id.edit_form",
      'parent' => 'parent_id',
      'parent_name' => "entity.$entity_type_id.edit_form", 
    ];
  }
}
