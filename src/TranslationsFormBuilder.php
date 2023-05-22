<?php
namespace Drupal\translations_pack;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormState;

class TranslationsFormBuilder {

  const FORM_CLASS = 'Drupal\translations_pack\TranslationForm';

  protected $formBuilder;

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $manager, FormBuilderInterface $builder, ModuleHandlerInterface $handler) {
    $this->entityTypeManager = $manager;
    $this->formBuilder = $builder;
    $this->moduleHandler = $handler;
  }

  public function getForm(EntityInterface $entity, $operation = 'default', array $form_state_additions = []) {
    $form_object = \Drupal::classResolver()->getInstanceFromDefinition(self::FORM_CLASS);
    $form_object
      //->setStringTranslation($this->stringTranslation)
      ->setStringTranslation(\Drupal::service('string_translation'))
      ->setModuleHandler($this->moduleHandler)
      ->setEntityTypeManager($this->entityTypeManager)
      ->setOperation('edit');
    $form_object->setEntity($entity);
    $form_state = (new FormState())->setFormState($form_state_additions);
    return $this->formBuilder->buildForm($form_object, $form_state);
  }

}
