<?php
namespace Drupal\translations_eck_pack;

use Symfony\Component\Routing\RouteCollection;
use Drupal\translations_pack\TranslationsPackHandler;
use Symfony\Component\Routing\Route;

class TranslationsPackEckHandler extends TranslationsPackHandler {

  const ADD_Controller = 
    '\Drupal\translations_eck_pack\Controller\TranslationsPackEckController::build_eck_add';

  protected function getOriginalAddRoute(RouteCollection $collection) {
    return $collection->get('eck.entity.add');
  }

  protected function addCreateAccess(Route $add_route, $entity_type_id) {
    // already has requirements cloned from `add-form` 
    $add_route->setRequirement('_access_translations_eck_pack_create', $entity_type_id);
  }
  
  protected function routesAllEnabled() {
    return TRUE;
  }

  protected function hasAddLink() {
    return TRUE;
  }

  protected function getAddTasksConfig() {
    return [
      'route_name' => 'eck.entity.add',
      'base_name' => 'eck.entity_content:eck.entity.add',
      'parent' => 'base_route',
      'parent_name' => 'eck.entity.add', 
    ];
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
