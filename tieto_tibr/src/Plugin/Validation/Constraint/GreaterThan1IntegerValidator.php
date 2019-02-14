<?php

namespace Drupal\tieto_tibr\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the GreaterThan1Integer constraint.
 */
class GreaterThan1IntegerValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // First check if the value is an integer.
    if (filter_var($value, FILTER_VALIDATE_INT) === FALSE) {
      // The value is not an integer, so a violation, aka error, is applied.
      // The type of violation applied comes from the constraint description
      // in step 1.
      $this->context->addViolation($constraint->notInteger);
    }

    // Next check if the value is greater then equival 1.
    if ($value < 0) {
      $this->context->addViolation($constraint->notGreaterThan1);
    }
  }

}
