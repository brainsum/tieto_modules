<?php

namespace Drupal\tieto_linkit\Plugin\Linkit\Matcher;

use Drupal\linkit\Plugin\Linkit\Matcher\EntityMatcher;

/**
 * Tieto Entity Matcher.
 *
 * @Matcher(
 *   id = "entity",
 *   label = @Translation("Entity"),
 *   deriver = "\Drupal\linkit\Plugin\Derivative\EntityMatcherDeriver"
 * )
 */
class TietoEntityMatcher extends EntityMatcher {

  /**
   * Builds the path string used in the match array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The matched entity.
   *
   * @return string
   *   The URL for this entity.
   */
  // @codingStandardsIgnoreLine
  protected function buildPath($entity) : string {
    // EntityMatcher doesn't declare the $entity variable's type. So we can't
    // too or we can get "Declaration of Methods should be Compatible with
    // Parent Methods" error.
    return '/' . $entity->getEntityTypeId() . '/' . $entity->id();
  }

}
