<?php

namespace Drupal\tieto_unpublish_ui\Helper;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class ModerationStateProcessor.
 *
 * @package Drupal\tieto_unpublish_ui\Helper
 */
class ModerationStateProcessor {

  /**
   * Return the labels.
   *
   * @return array
   *   Assoc array with state=>label pairs.
   */
  public static function getStateLabels() {
    return [
      'published' => t('Publish'),
      'unpublished' => t('Save'),
      'trash' => t('Archive'),
    ];
  }

  /**
   * Callback for processing actions.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The form.
   *
   * @return array
   *   The original or updated element
   */
  public static function processActions(array $element, FormStateInterface $form_state, array &$form) {
    // @todo: Check why this runs 4 times for profiled news.
    // It runs once OK, 3 times without the #options.
    //
    // Override labels.
    if (empty($element['#options'])) {
      return $element;
    }

    $options = \array_merge($element['#options'], static::getStateLabels());

    foreach ($options as $id => $label) {
      $form['actions']['moderation_state_' . $id]['#value'] = $label;
    }

    return $element;
  }

}
