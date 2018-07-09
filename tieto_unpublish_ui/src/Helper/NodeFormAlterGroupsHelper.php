<?php

namespace Drupal\tieto_unpublish_ui\Helper;

/**
 * Class NodeFormAlterGroupsHelper.
 *
 * @package Drupal\tieto_unpublish_ui\Helper
 */
class NodeFormAlterGroupsHelper extends NodeFormAlterHelperBase {

  /**
   * The fields that need to be unset.
   */
  const FIELDS = [
    'path',
    'path_settings',
    'url_redirects',
    'author',
  ];

  /**
   * Helper function for creating groups.
   *
   * @return array
   *   The render array.
   */
  public function createNodeGroups() {
    $build = [
      '#type' => 'container',
      '#weight' => 0,
    ];

    foreach (static::FIELDS as $field) {
      if (!isset($this->form[$field])) {
        continue;
      }
      $build[$field] = $this->form[$field];
      if (
        isset($build[$field]['#group'])
        && $build[$field]['#group'] === 'advanced'
      ) {
        unset($build[$field]['#group']);
      }
    }

    return $build;
  }

}
