<?php
namespace Drupal\translations_pack;

use Drupal\Core\Routing\NullRouteMatch;

class MockRouteMatch extends NullRouteMatch {
  public $entity;
  
  public function __construct($entity) {
    $this->entity = $entity;
  }

  public function getParameter($parameter_name) {
    return $this->entity;
  }
}
