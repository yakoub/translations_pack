<?php
namespace Drupal\translations_pack;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormState;

class TranslationsFormBuilder {

  const FORM_CLASS = 'Drupal\translations_pack\Form\TranslationForm';

  protected $formBuilder;

  protected $entityTypeManager;

  protected $moduleHandler;

  protected $translation_form = FALSE;

  protected array $forms = [];

  public function __construct(EntityTypeManagerInterface $manager, FormBuilderInterface $builder, ModuleHandlerInterface $handler) {
    $this->entityTypeManager = $manager;
    $this->formBuilder = $builder;
    $this->moduleHandler = $handler;
  }

  public function setTranslationMode() {
    $this->translation_form = TRUE;
  }

  public function setOriginalMode() {
    $this->translation_form = FALSE;
  }

  public function getForm(EntityInterface $entity, $operation = 'default', array $form_state_additions = []) {
    if ($this->translation_form) {
      $form_object = $this->getTranslationForm($entity, $operation);
    }
    else {
      $form_object = $this->getOriginalForm($entity, $operation);
    }

    $form_object->setEntity($entity);
    $langcode = $form_state_additions['langcode'] ?? $entity->language()->getId();
    if ($this->translation_form) {
      $form_state = new TranslationsFormState();
      $form_state->original_entity = $entity;
    }
    else {
      $form_state = new FormState();
    }
    $form_state->setFormState($form_state_additions);
    $this->forms[$langcode] = [$form_object, &$form_state];
    $build = $this->formBuilder->buildForm($form_object, $form_state);
    return $build;
  }

  public function getTranslationForm(EntityInterface $entity, $operation = 'default') {
    $form_object = \Drupal::classResolver()->getInstanceFromDefinition(self::FORM_CLASS);
    $form_object
      //->setStringTranslation($this->stringTranslation)
      ->setStringTranslation(\Drupal::service('string_translation'))
      ->setModuleHandler($this->moduleHandler)
      ->setEntityTypeManager($this->entityTypeManager)
      ->setOperation('edit');
    return $form_object;
  }

  public function getOriginalForm(EntityInterface $entity, $operation = 'default') {
    return $this->entityTypeManager
      ->getFormObject($entity
      ->getEntityTypeId(), $operation);
  }

  
  public function getFormStates() {
    return $this->forms;
  }

}
