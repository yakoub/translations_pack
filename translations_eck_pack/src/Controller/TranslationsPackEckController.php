<?php

namespace Drupal\translations_eck_pack\Controller;

use Drupal\translations_pack\Controller\TranslationsPackController;
use Drupal\translations_pack\MockRouteMatch;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\eck\EckEntityTypeInterface;

class TranslationsPackEckController extends TranslationsPackController {

   function build_eck_add($form_operation, Request $request, RouteMatchInterface $route_match, EckEntityTypeInterface $eck_entity_type, $eck_entity_bundle) {
    $bundleStorage = $this->getBundleStorage($eck_entity_type);
    if (!$bundleStorage->load($eck_entity_bundle)) {
      throw new NotFoundHttpException($this->t('Bundle %bundle does not exist', ['%bundle' => $eck_entity_bundle]));
    }

    $entityStorage = $this->entityTypeManager()->getStorage($eck_entity_type->id());

    $entity = $entityStorage->create(['type' => $eck_entity_bundle]);
    $language = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    $langcode_key = $entity->getEntityType()->getKey('langcode');
    $entity->set($langcode_key, $language->getId());
    $route_match = new MockRouteMatch($entity);
    return $this->build_pack($eck_entity_type->id(), $request, $route_match);
  }

  private function getBundleStorage(EckEntityTypeInterface $eck_entity_type) {
    $entityTypeBundle = "{$eck_entity_type->id()}_type";
    $bundleStorage = $this->entityTypeManager()->getStorage($entityTypeBundle);
    return $bundleStorage;
  }
}
