<?php

namespace Drupal\translations_inline_pack;

use Drupal\Core\Entity\Entity\EntityFormDisplay;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityUntranslatableFieldsConstraint;
use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraint;

class PackInlineFormDisplay extends EntityFormDisplay {

  /**
   * {@inheritdoc}
   */
  public function validateFormValues(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    $violations = $entity->validate();
    $violations->filterByFieldAccess();
    $found = [];
    foreach ($violations as $offset => $violation) {
      if ($violation->getConstraint() instanceof EntityChangedConstraint) {
        $this->getLogger('translations_inline_pack')->debug('entity changed violation');
        $found[] = $offset;
      }
      if ($violation->getConstraint() instanceof EntityUntranslatableFieldsConstraint) {
        $this->getLogger('translations_inline_pack')->debug('untranslatable violation');
        $found[] = $offset;
      }
    }
    foreach ($found as $offset) {
      $violations->remove($offset);
    }

    // Flag entity level violations.
    foreach ($violations->getEntityViolations() as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      $form_state->setError($form, $violation->getMessage());
    }

    $this->flagWidgetsErrorsFromViolations($violations, $form, $form_state);
  }
}
