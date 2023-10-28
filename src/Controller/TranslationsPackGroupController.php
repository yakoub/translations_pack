<?php

namespace Drupal\translations_pack\Controller;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\translations_pack\PackConfig;

class TranslationsPackGroupController extends TranslationsPackController {
  use GroupRelationshipTrait; 

  protected $entity;
  protected $form;
  protected $response_exception = NULL;

  public function build_group(GroupInterface $group, $plugin_id, Request $request, RouteMatchInterface $route_match) {
    try {
      $this->form = $this->createForm($group, $plugin_id);
    }
    catch (EnforcedResponseException $e) {
      $this->response_exception = $e;
    }
    $entity_type_id = $this->entity->getEntityTypeId();
    $bundle = $this->entity->bundle();
    if ($entity_type_id == 'group_content' || PackConfig::bundleEnabled($entity_type_id, $bundle)) {
      if ($this->response_exception) {
        throw $this->response_exception;
      }
      return $this->form;
    }
    return $this->build_pack($entity_type_id, $request, $route_match);
  }

  protected function getRequestEntity(RouteMatchInterface $route_match, $entity_type_id) {
    return $this->entity;
  }

  protected function getOriginalForm($entity) {
    if ($this->response_exception) {
      throw $this->response_exception;
    }
    return $this->form;
  }

}
