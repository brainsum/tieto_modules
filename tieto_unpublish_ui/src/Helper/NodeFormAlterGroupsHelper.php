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

      array_walk_recursive($build[$field], [$this, 'removeGroup']);
    }
    return $build;
  }

  /**
   * Remove groups.
   *
   * @param string $item
   *   The item.
   * @param string $key
   *   The key.
   */
  public function removeGroup(&$item, $key) {
    if ($key == '#group' && $item == 'advanced') {
      $item = NULL;
    }
  }

}
