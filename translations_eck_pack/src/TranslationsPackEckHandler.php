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

  protected function getAddBasename() {
    return 'eck.entity.add';
  }

  protected function getEditBasename() {
    $entity_type_id = $this->entity_type->id();
    return "eck.entity_content:{$entity_type_id}.eck_edit_tab";
  }

  protected function hasAddLink() {
    return TRUE;
  }
}
