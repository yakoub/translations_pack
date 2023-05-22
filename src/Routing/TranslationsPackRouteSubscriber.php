<?php

namespace Drupal\translations_pack\Routing;

use Drupal\content_translation\Routing\ContentTranslationRouteSubscriber;
use Drupal\content_translation\ContentTranslationManager;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for entity translation routes.
 */
class TranslationsPackRouteSubscriber extends ContentTranslationRouteSubscriber {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      // Inherit admin route status from edit route, if exists.
      $is_admin = FALSE;
      $route_name = "entity.$entity_type_id.edit_form";
      if ($edit_route = $collection->get($route_name)) {
        $is_admin = (bool) $edit_route->getOption('_admin_route');
      }

      $load_latest_revision = ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id);

      if (
        $entity_type->hasLinkTemplate('drupal:content-translation-add') &&
        $entity_type->hasLinkTemplate('add-form')
      ) {
        $add_route_path = $entity_type->getLinkTemplate('add-form') . '/pack';
        $route = clone $collection->get("entity.{$entity_type_id}.add_form");
        $route->setPath($add_route_path);
        $defaults = $route->getDefaults();
        if (isset($defaults['_entity_form'])) {
          $defaults['form_operation'] = $defaults['_entity_form'];
        }
        else {
          $defaults['form_operation'] = "$entity_type_id.default";
        }
        unset($defaults['_entity_form']);
        $defaults['_controller'] =
          '\Drupal\translations_pack\Controller\TranslationsPackController::build_add';
        $route->setDefaults($defaults);
        // already has requirements cloned from `add-form` 
        $route->setRequirement('_access_translations_pack_create', $entity_type_id);
        $collection->add("entity.$entity_type_id.translations_pack_add", $route);
      }

      if ($entity_type->hasLinkTemplate('drupal:content-translation-edit')) {
        $edit_route_name = $entity_type->getLinkTemplate('edit-form') . '/pack';
        $route = new Route($edit_route_name);
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
        $collection->add("entity.$entity_type_id.translations_pack_edit", $route);
      }
    }
  }
}
