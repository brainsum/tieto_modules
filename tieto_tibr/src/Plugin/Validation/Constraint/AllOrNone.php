<?php

namespace Drupal\tieto_tibr\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a greater than 0 and integer.
 *
 * @Constraint(
 *   id = "all_or_none",
 *   label = @Translation("All is set or none of them", context = "Validation"),
 * )
 */
class AllOrNone extends Constraint {

  /**
   * Default message.
   *
   * Will be shown if from the values are not set all or none of them.
   *
   * @var string
   */
  public $notAllOrNone = 'From Tibbr Subject Name and ID all values needs to be set or none of them';

}
