<?php
namespace Drupal\translations_pack\handlers;

use Drupal\node\NodeTranslationHandler as HandlerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

class NodeTranslationHandler extends HandlerBase {
  use HandlerTrait;
}
