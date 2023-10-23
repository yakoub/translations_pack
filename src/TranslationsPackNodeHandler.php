<?php
namespace Drupal\translations_pack;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\translations_pack\PackConfig;
use Drupal\content_translation\ContentTranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

class TranslationsPackNodeHandler extends TranslationsPackHandler {
  
  const ADD_Controller = '\Drupal\translations_pack\Controller\TranslationsPackNodeController::build_add_node';

  protected function getOriginalAddRoute(RouteCollection $collection) {
    return $collection->get('node.add');
  }

  protected function hasAddLink() {
    return TRUE;
  }

  protected function getAddTasksConfig() {
    return [
      'route_name' => 'node.add',
      'base_name' => 'node.add',
      'parent' => 'base_route',
      'parent_name' => 'node.add',
    ];
  }
}
