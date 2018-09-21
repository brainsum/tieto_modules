<?php

/**
 * @file
 * Build Tieto unpiblish UI related tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function tieto_unpublish_ui_token_info() {
  $type[] = [
    'name' => t('Unpublish UI'),
    'description' => t('Tokens related to unpublish UI.'),
    'needs-data' => 'node',
  ];

  // Token for last-pusblish-date.
  $tokens['unpublish-ui-last-publish-date'] = [
    'name' => t('Last publish date'),
    'description' => t('Last publish date or NULL.'),
    'type' => 'date',
  ];

  return [
    'types' => ['node' => $type],
    'tokens' => ['node' => $tokens],
  ];
}

/**
 * Implements hook_tokens().
 */
function tieto_unpublish_ui_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'node' && !empty($data['node']) && $data['node']->isPublished()) {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'unpublish-ui-last-publish-date':
          $revisionManager = \Drupal::service('tieto_unpublish_ui.node_revision_manager');
          $revision = $revisionManager->loadLatestPublishedRevision($data['node']);
          if (NULL !== $revision) {
            $replacements[$original] = $revision->getChangedTime();
          }
          break;
      }
    }

    if ($dateTokens = \Drupal::token()->findWithPrefix($tokens, 'unpublish-ui-last-publish-date')) {
      $revisionManager = \Drupal::service('tieto_unpublish_ui.node_revision_manager');
      $revision = $revisionManager->loadLatestPublishedRevision($data['node']);
      if (NULL !== $revision) {
        $replacements += \Drupal::token()
          ->generate('date', $dateTokens, ['date' => $revision->getChangedTime()], $options, $bubbleable_metadata);
      }
    }
  }

  return $replacements;
}
