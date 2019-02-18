<?php

/**
 * @file
 * Provide views data.
 */

/**
 * Implements hook_views_data_alter().
 */
function tieto_general_ui_views_data_alter(&$data) {
  $data['node']['tieto_general_ui_clone_link'] = [
    'title' => \t('Link to clone Content'),
    'field' => [
      'title' => \t('Link to clone Content'),
      'help' => \t('Provide a clone link to the Content.'),
      'id' => 'tieto_general_ui_clone_link',
    ],
  ];
}
