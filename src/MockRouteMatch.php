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
      $mock_context = new class($language) {
        public function __construct(protected LanguageInterface $language) {}
        public function getContextValue() { return $this->language; }
      };
      $context_key = '@language.current_language_context:' . LanguageInterface::TYPE_CONTENT; 
      $context = [$context_key => $mock_context];
      $this->entity = $entity_repository
        ->getActive($entity->getEntityTypeId(), $entity->id(), $context);
    }
  }

  public function getParameter($parameter_name) {
    return $this->entity;
  }
}
