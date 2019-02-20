<?php

namespace Drupal\tieto_tibr\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the AllOrNone constraint.
 */
class AllOrNoneValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint): void {
    foreach ($items->getValue() as $item) {
      if (
        (empty($item['tibbr_subject_id']) && !empty($item['tibbr_subject_name']))
        || (!empty($item['tibbr_subject_id']) && empty($item['tibbr_subject_name']))
      ) {
        $this->context->addViolation($constraint->notAllOrNone);
      }
    }
  }

}
