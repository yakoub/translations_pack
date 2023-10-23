<?php

namespace Drupal\translations_pack\Controller;

use Drupal\translations_pack\MockRouteMatch;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\NodeType;

class TranslationsPackNodeController extends TranslationsPackController {

  public function build_add_node(NodeType $node_type, Request $request) {
    $values = ['type' => $node_type->id()];
    $node = $this->entityTypeManager()->getStorage('node')->create($values);
    $language = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    $node->set('langcode', $language->getId());
    $route_match = new MockRouteMatch($node);
    return $this->build_pack('node', $request, $route_match);
  }
}
