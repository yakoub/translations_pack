<?php

namespace Drupal\translations_pack\Access;

use Drupal\content_translation\Access\ContentTranslationOverviewAccess;
use Drupal\translations_pack\PackConfig;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\translations_pack\MockRouteMatch;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Access check for translation pack create.
 */
class TranslationsPackEditAccess implements AccessInterface {

  public function access(RouteMatchInterface $route_match, AccountInterface $account, $entity_type_id) {
    $entity = $route_match->getParameter($entity_type_id);
    if (!PackConfig::enabled($entity_type_id, $entity->bundle())) {
      return AccessResult::forbidden('disabled by translation pack config');
    }
    return AccessResult::allowed();
  }

}
