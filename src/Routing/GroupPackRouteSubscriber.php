<?php

namespace Drupal\translations_pack\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


class GroupPackRouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.group_content.create_form')) {
      $defaults = $route->getDefaults();
      $defaults['_controller'] =
        '\Drupal\translations_pack\Controller\TranslationsPackGroupController::build_group';
      $route->setDefaults($defaults);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    // Should run after AdminRouteSubscriber so the routes can inherit admin
    // status of the edit routes on entities. Therefore priority -210.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -210];
    return $events;
  }
}
