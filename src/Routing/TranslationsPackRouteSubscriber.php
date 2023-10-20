<?php

namespace Drupal\translations_pack\Routing;

use Drupal\content_translation\Routing\ContentTranslationRouteSubscriber;
use Drupal\content_translation\ContentTranslationManager;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\translations_pack\PackConfig;

/**
 * Subscriber for entity translation routes.
 */
class TranslationsPackRouteSubscriber extends ContentTranslationRouteSubscriber {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entity_type_id => $entity_type) {

      if ($entity_type_id == 'group_content') {
        continue;
      }
      // Inherit admin route status from edit route, if exists.
      $is_admin = FALSE;
      $route_name = "entity.$entity_type_id.edit_form";
      $original_route = FALSE;
      if ($edit_route = $collection->get($route_name)) {
        $is_admin = (bool) $edit_route->getOption('_admin_route');
      }

      $load_latest_revision = ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id);
      $all_enabled = PackConfig::enabled($entity_type_id);

      if (
        $entity_type->hasLinkTemplate('drupal:content-translation-add') &&
        $entity_type->hasLinkTemplate('add-form')
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

      if ($entity_type->hasLinkTemplate('drupal:content-translation-edit')) {
        $edit_route_path = $entity_type->getLinkTemplate('edit-form');

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
    }
  }
}
