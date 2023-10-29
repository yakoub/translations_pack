<?php

namespace Drupal\translations_eck_pack\Access;

use Drupal\content_translation\Access\ContentTranslationOverviewAccess;
use Drupal\translations_pack\PackConfig;
use Drupal\eck\EckEntityTypeInterface;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\translations_pack\MockRouteMatch;
use Drupal\Core\Access\AccessResult;

/**
 * Access check for translation pack create.
 */
class TranslationsEckPackCreateAccess extends ContentTranslationOverviewAccess {

  public function access(RouteMatchInterface $route_match, AccountInterface $account, $ignore = NULL) {
    $eck_entity_bundle = $route_match->getParameter('eck_entity_bundle');
    $eck_entity_type = $route_match->getParameter('eck_entity_type');
    $entity_type_id = $eck_entity_type->id();

    $bundleStorage = $this->getBundleStorage($eck_entity_type);
    if (!$bundleStorage->load($eck_entity_bundle)) {
      throw new NotFoundHttpException($this->t('Bundle %bundle does not exist', ['%bundle' => $eck_entity_bundle]));
    }

    $entityStorage = $this->entityTypeManager->getStorage($eck_entity_type->id());

    $entity = $entityStorage->create(['type' => $eck_entity_bundle]);

    if (!PackConfig::bundleEnabled($entity_type_id, $eck_entity_bundle)) {
      return AccessResult::forbidden('disabled by translation pack config');
    }

    $route_match = new MockRouteMatch($entity);
    return parent::access($route_match, $account, $entity_type_id);
  }

  private function getBundleStorage(EckEntityTypeInterface $eck_entity_type) {
    $entityTypeBundle = "{$eck_entity_type->id()}_type";
    $bundleStorage = $this->entityTypeManager->getStorage($entityTypeBundle);
    return $bundleStorage;
  }
}
