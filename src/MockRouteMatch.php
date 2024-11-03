<?php
namespace Drupal\translations_pack;

use Drupal\Core\Routing\NullRouteMatch;
use Drupal\Core\Language\LanguageInterface;

class MockRouteMatch extends NullRouteMatch {
  public $entity;
  
  public function __construct($entity, LanguageInterface $language) {
    if ($entity->isNew()) {
      $this->entity = $entity;
    }
    else {
      $entity_repository = \Drupal::service('entity.repository');
      $context = ['langcode' => $language->getId()];
      $this->entity = $entity_repository
        ->getActive($entity->getEntityTypeId(), $entity->id(), $context);

      if (!$this->entity || !$this->entity->hasTranslation($language->getId())) {
        $this->entity = $entity_repository
          ->getCanonical($entity->getEntityTypeId(), $entity->id());
      }
    }
  }

  public function getParameter($parameter_name) {
    return $this->entity;
  }
}
