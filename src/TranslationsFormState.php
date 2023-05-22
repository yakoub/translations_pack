<?php
namespace Drupal\translations_pack;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormState;

class TranslationsFormState extends FormState {

  public EntityInterface $original_entity;
  public $saved_entity;
}
