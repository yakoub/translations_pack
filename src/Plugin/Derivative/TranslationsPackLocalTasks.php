<?php

namespace Drupal\translations_pack\Plugin\Derivative;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
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
      if ($entity_type_id == 'group_content') {
        continue;
      }
      $routes = [];
      $all_enabled = PackConfig::enabled($entity_type_id);

      $has_add_link = 
        $entity_type->hasLinkTemplate('drupal:content-translation-add') &&
        $entity_type->hasLinkTemplate('add-form');

      if ($has_add_link || $entity_type_id == 'node') {
        if ($all_enabled) {
          $pack_name = "entity.$entity_type_id.single_add_form";
          $base_title = $this->t('Translations');
          $pack_title = $this->t('Single');
        }
        else {
          $pack_name = "entity.$entity_type_id.pack_add_form";
          $base_title = $this->t('Single');
          $pack_title = $this->t('Translations');
        }

        if ($entity_type_id == 'node') {
          $base_name = "node.add";
        }
        else {
          $base_name = "entity.$entity_type_id.add_form";
        }
        $parent = 'base_route';

        $this->derivatives[$base_name] = [
          'entity_type' => $entity_type_id,
          'title' => $base_title,
          'route_name' => $base_name,
          $parent => $base_name,
        ] + $base_plugin_definition;

        $this->derivatives[$pack_name] = [
          'entity_type' => $entity_type_id,
          'title' => $pack_title,
          'route_name' => $pack_name,
          $parent => $base_name,
        ] + $base_plugin_definition;
      }

      if ($entity_type->hasLinkTemplate('drupal:content-translation-edit')) {
        if ($all_enabled) {
          $pack_name = "entity.$entity_type_id.single_edit_form";
          $base_title = $this->t('Translations');
          $pack_title = $this->t('Single');
        }
        else {
          $pack_name = "entity.$entity_type_id.pack_edit_form";
          $base_title = $this->t('Single');
          $pack_title = $this->t('Translations');
        }
        $base_name = "entity.$entity_type_id.edit_form";
        $parent = 'parent_id';

        $this->derivatives[$base_name] = [
          'entity_type' => $entity_type_id,
          'title' => $base_title,
          'route_name' => $base_name,
          $parent => $base_name,
        ] + $base_plugin_definition;

        $this->derivatives[$pack_name] = [
          'entity_type' => $entity_type_id,
          'title' => $pack_title,
          'route_name' => $pack_name,
          $parent => $base_name,
        ] + $base_plugin_definition;

      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
