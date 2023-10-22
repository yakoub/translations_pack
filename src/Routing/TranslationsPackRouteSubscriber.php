<?php

namespace Drupal\translations_pack\Routing;

use Drupal\content_translation\Routing\ContentTranslationRouteSubscriber;
use Drupal\content_translation\ContentTranslationManager;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\translations_pack\PackConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Subscriber for entity translation routes.
 */
class TranslationsPackRouteSubscriber extends ContentTranslationRouteSubscriber {

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(ContentTranslationManagerInterface $content_translation_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->contentTranslationManager = $content_translation_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      if ($entity_type_id == 'group_content') {
        continue;
      }
      if (!$entity_type->hasHandlerClass('translations_pack')) {
        continue;
      }
      $handler = $this->entityTypeManager->getHandler($entity_type_id, 'translations_pack');
      $handler->alterCreateRoute($collection);
      if ($entity_type->hasLinkTemplate('drupal:content-translation-edit')) {
        $handler->alterUpdateRoute($collection);
      }
    }
  }
}
