<?php

namespace Drupal\tieto_tibr\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a greater than 0 and integer.
 *
 * @Constraint(
 *   id = "max_length",
 *   label = @Translation("Is greater than max length", context = "Validation"),
 * )
 */
class MaxLength extends Constraint {

  /**
   * The message that will be shown if the value is greater than the max length.
   *
   * @var string
   */
  public $notLessThan = '"%value" is greater than %maxlength characters';

  /**
   * This contains the maximum allowed length of the field.
   *
   * @var int
   */
  public $maxLength;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption() {
    return 'maxLength';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return 'maxLength';
  }

}
