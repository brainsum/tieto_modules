<?php

/**
 * @file
 * Provide views data for the node_revision_status module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data_alter().
 */
function node_revision_status_views_data_alter(array &$data) {
  $data['node']['node_latest_revision_status'] = [
    'title' => \t('Status of the latest revision'),
    'field' => [
      'title' => \t('Status of the latest revision'),
      'help' => \t('Display the latest revision status.'),
      'id' => 'node_latest_revision_status',
    ],
  ];
}
