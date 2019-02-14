<?php

namespace Drupal\tieto_tibr\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the GreaterThan0Integer constraint.
 */
class MaxLengthValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (\strlen($value) > $constraint->maxLength) {
      $this->context->addViolation($constraint->notLessThan, ['%value' => $value, '%maxLength' => $constraint->maxLength]);
    }
  }

}
