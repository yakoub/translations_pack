<?php

namespace Drupal\translations_pack\Plugin\Derivative;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\translations_pack\PackConfig;

/**
 * Provides dynamic local tasks for content translation.
 */
class TranslationsPackLocalTasks extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct($base_plugin_id, ContentTranslationManagerInterface $content_translation_manager, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->contentTranslationManager = $content_translation_manager;
    $this->stringTranslation = $string_translation;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('content_translation.manager'),
      $container->get('string_translation'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Create tabs for all possible entity types.
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      if ($entity_type_id == 'group_content') {
        continue;
      }
      if (!$entity_type->hasHandlerClass('translations_pack')) {
        continue;
      }
      $handler = $this->entityTypeManager->getHandler($entity_type_id, 'translations_pack');
      $handler->deriveLocalTasks($this->derivatives, $base_plugin_definition);
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
