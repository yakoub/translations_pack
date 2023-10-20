<?php

namespace Drupal\translations_pack\Access;

use Drupal\content_translation\Access\ContentTranslationOverviewAccess;
use Drupal\translations_pack\PackConfig;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\translations_pack\MockRouteMatch;
use Drupal\Core\Access\AccessResult;

/**
 * Access check for translation pack create.
 */
class TranslationsPackCreateAccess extends ContentTranslationOverviewAccess {

  public function access(RouteMatchInterface $route_match, AccountInterface $account, $entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $operation = 'default';
    if ($entity_type->getFormClass('add')) {
      $operation = 'add';
    }
    $form_object = $this->entityTypeManager->getFormObject($entity_type_id, $operation);
    $entity = $form_object->getEntityFromRouteMatch($route_match, $entity_type_id);
    $bundle = $entity->bundle();
    if (!PackConfig::enabled($entity_type_id, $bundle)) {
      return AccessResult::forbidden('disabled by translation pack config');
    }

    $route_match = new MockRouteMatch($entity);
    return parent::access($route_match, $account, $entity_type_id);
  }

}
