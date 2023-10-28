<?php
namespace Drupal\translations_eck_pack;

use Symfony\Component\Routing\RouteCollection;
use Drupal\translations_pack\TranslationsPackHandler;
use Drupal\translations_pack\PackConfig;
use Symfony\Component\Routing\Route;
use Drupal\content_translation\ContentTranslationManager;

class TranslationsPackEckHandler extends TranslationsPackHandler {

  const ADD_Controller = 
    '\Drupal\translations_eck_pack\Controller\TranslationsPackEckController::build_eck_add';

  protected function getEckAddRoute(RouteCollection $collection) {
    $add_route = $collection->get('eck.entity.pack');
    if (!$add_route) {
      $add_route = clone $collection->get('eck.entity.add');
      $add_route->setPath($add_route->getPath() . '/pack');
      $collection->add('eck.entity.pack', $add_route);
    }
    return $add_route;
  }
  
  public function alterCreateRoute(RouteCollection $collection) {
    $is_admin = $this->adminRoute($collection);
    $entity_type_id = $this->entity_type->id();
    $config_status = $this->configStatus();

    if ($config_status == PackConfig::DISABLED) {
      return;
    }
    $add_route = $this->getEckAddRoute($collection);
    $defaults = $add_route->getDefaults();
    $defaults['entity_type_id'] = $entity_type_id;
    $defaults['_controller'] = static::ADD_Controller;
    $add_route->setDefaults($defaults);
    $this->addCreateAccess($add_route, $entity_type_id);
  }

  protected function addCreateAccess(Route $add_route, $entity_type_id) {
    $add_route->setRequirement('_access_translations_eck_pack_create', $entity_type_id);
  }

  protected function hasAddLink() {
    return FALSE;
  }

  protected function getEditTasksConfig() {
    $entity_type_id = $this->entity_type->id();
    return [
      'route_name' => "entity.$entity_type_id.edit_form",
      'base_name' => "eck.entity_content:$entity_type_id.eck_edit_tab",
      'parent' => 'parent_id',
      'parent_name' => "eck.entity_content:$entity_type_id.eck_edit_tab", 
    ];
  }

}
