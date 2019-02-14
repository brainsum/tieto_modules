<?php

namespace Drupal\tieto_tibr\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a greater than 1 and integer.
 *
 * @Constraint(
 *   id = "greater_than_1_integer",
 *   label = @Translation("Greater than 1 Integer", context = "Validation"),
 * )
 */
class GreaterThan1Integer extends Constraint {

  /**
   * The message that will be shown if the value is not an integer.
   *
   * @var string
   */
  public $notInteger = 'Subject ID is not an integer';

  /**
   * The message that will be shown if the value is not greater than 1.
   *
   * @var string
   */
  public $notGreaterThan1 = 'Subject ID is not greater than 1';

}
