<?php

namespace Drupal\translations_pack\Plugin\Derivative;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

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

  public function __construct($base_plugin_id, ContentTranslationManagerInterface $content_translation_manager, TranslationInterface $string_translation) {
    $this->basePluginId = $base_plugin_id;
    $this->contentTranslationManager = $content_translation_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('content_translation.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Create tabs for all possible entity types.
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      if ($entity_type_id == 'node') {
        continue;
      }
      if ($entity_type_id == 'group_content') {
        $this->getGroupDerivative($base_plugin_definition);
        continue;
      }
      
      $routes = [];

      if (
        $entity_type->hasLinkTemplate('drupal:content-translation-add') &&
        $entity_type->hasLinkTemplate('add-form')
      ) {
        $translation_pack_name = "entity.$entity_type_id.translations_pack_add";
        $base_route_name = "entity.$entity_type_id.add_form";
        $routes[$base_route_name] = [$translation_pack_name, 'base_route'];
      }

      if ($entity_type->hasLinkTemplate('drupal:content-translation-edit')) {
        $translation_pack_name = "entity.$entity_type_id.translations_pack_edit";
        $base_route_name = "entity.$entity_type_id.edit_form";
        $routes[$base_route_name] = [$translation_pack_name, 'parent_id'];
      }

      foreach ($routes as $base_name => $config) {
        list($pack_name, $parent) = $config;
        $this->derivatives[$base_name] = [
          'entity_type' => $entity_type_id,
          'title' => $this->t('Single'),
          'route_name' => $base_name,
          $parent => $base_name,
        ] + $base_plugin_definition;

        $this->derivatives[$pack_name] = [
          'entity_type' => $entity_type_id,
          'title' => $this->t('Translations'),
          'route_name' => $pack_name,
          $parent => $base_name,
        ] + $base_plugin_definition;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

  function getGroupDerivative($base_plugin_definition) {
    $base_name = 'entity.group_content.create_form';
    $pack_name = 'entity.group_content.translations_pack_add';

    $this->derivatives['translations_pack.group_content_add'] = [
      'entity_type' => 'group_content',
      'title' => $this->t('Single'),
      'route_name' => $base_name,
      'base_route' => $base_name,
    ] + $base_plugin_definition;

    $this->derivatives['translations_pack.group_packed_form_add'] = [
      'entity_type' => 'group_content',
      'title' => $this->t('Translations'),
      'route_name' => $pack_name,
      'base_route' => $base_name,
    ] + $base_plugin_definition;
  }
}
