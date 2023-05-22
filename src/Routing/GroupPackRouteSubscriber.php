<?php

namespace Drupal\translations_pack\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


class GroupPackRouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection) {
    if ($group_route = $collection->get('entity.group_content.create_form')) {
      $add_route_path = $group_route->getPath() . '/pack';
      $route = clone $group_route;
      $route->setPath($add_route_path);
      $defaults = $route->getDefaults();
      $defaults['_controller'] =
        '\Drupal\translations_pack\Controller\TranslationsPackGroupController::build_group';
      $route->setDefaults($defaults);
      // route already has '_group_content_create_entity_access'
      $route->setRequirement('_access_translations_pack_group', 'TRUE');
      $collection->add("entity.group_content.translations_pack_add", $route);
    }
  }

  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    // Should run after AdminRouteSubscriber so the routes can inherit admin
    // status of the edit routes on entities. Therefore priority -210.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -210];
    return $events;
  }
}
