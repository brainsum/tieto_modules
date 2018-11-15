<?php

namespace Drupal\tieto_link_customization\Permission;

/**
 * Class LinkPermissions.
 *
 * @package Drupal\tieto_link_customization\Permission
 */
class LinkPermissions {

  const ATTRIBUTES = [
    'title',
    'class',
    'id',
    'rel',
  ];

  /**
   * Callback for permissions.
   *
   * @return array
   *   Permissions.
   */
  public function permissions(): array {
    $permissions = [];

    foreach (static::ATTRIBUTES as $attribute) {
      $permissions["edit $attribute link attribute"] = [
        'title' => "Edit the $attribute attribute",
        'description' => "Allow access for the $attribute attribute, not just target (e.g via the link button in editors)",
      ];
    }

    return $permissions;
  }

}
