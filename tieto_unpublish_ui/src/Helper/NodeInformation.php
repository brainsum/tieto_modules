<?php

namespace Drupal\tieto_unpublish_ui\Helper;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class NodeInformation.
 *
 * @package Drupal\tieto_unpublish_ui\Helper
 */
class NodeInformation extends NodeFormAlterHelperBase {

  /**
   * Return the full metadata array.
   *
   * @return array
   *   The metadata array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getMetaData() {
    $build = [
      'node' => [
        'is_new' => $this->node()->isNew(),
        'has_published_version' => $this->hasPublishedVersion(),
        'can_be_republished' => $this->canBeRepublished(),
      ],
      'actions' => $this->getActions(),
      'info' => [
        'title' => $this->getInfoTitle(),
      ],
      'meta' => [
        'title' => $this->getMetaTitle(),
        'last_publish_date' => $this->getLastPublishDate(),
        'first_publish_date' => $this->getFirstPublishDate(),
        'unpublish_date' => $this->getMetaUnpublishDate(),
        'view_link' => $this->getNodeViewLink(),
        'author' => $this->getMetaAuthor(),
      ],
      'notification_actions' => $this->getNotificationActions(),
    ];

    if (!$this->node()->isNew()) {
      $build['info']['last_update'] = $this->getInfoLastUpdated();
      $build['info']['prev_versions'] = $this->getInfoPrevVersions();
    }

    return $build;
  }

  /**
   * Return the last unpublish date.
   *
   * @return array|null
   *   The render array, or null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getMetaUnpublishDate() {
    $revision = $this->nodeRevisionManager->loadLatestUnpublishedRevision($this->node());
    if (NULL === $revision || !$revision->isDefaultRevision()) {
      return [];
    }

    $revisionDate = $revision->getChangedTime();

    return [
      '#type' => 'container',
      '#weight' => 10,
      '#attributes' => [
        'class' => [
          'node-meta--unpublish-date',
        ],
      ],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'label',
        '#attributes' => [
          'class' => [
            'node-meta-label',
            'node-meta--unpublish-date--label',
          ],
        ],
        '#value' => $this->t('Unpublish date'),
      ],
      'value' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => [
            'node-meta-value',
            'node-meta--unpublish-date--value',
          ],
        ],
        '#value' => $this->dateFormatter->format($revisionDate, 'tieto_date'),
      ],
    ];
  }

  /**
   * Get the author (publisher or unpublisher).
   *
   * @return array
   *   Author render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getMetaAuthor() {
    $build = [
      '#type' => 'container',
      '#weight' => 15,
      '#attributes' => [
        'class' => [
          'node-meta--author',
        ],
      ],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'label',
        '#attributes' => [
          'class' => [
            'node-meta-label',
            'node-meta--author--label',
          ],
        ],
        '#value' => $this->t('Publisher'),
      ],
      'value' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => [
            'node-meta-value',
            'node-meta--author--value',
          ],
        ],
        '#value' => $this->node()->getOwner()->getDisplayName(),
      ],
    ];

    $revision = $this->nodeRevisionManager->loadLatestUnpublishedRevision($this->node());
    if (NULL === $revision || !$revision->isDefaultRevision()) {
      return $build;
    }

    $build['label']['#value'] = $this->t('Unpublished by');
    $build['value']['#value'] = $revision->getOwner()->getDisplayName();

    return $build;
  }

  /**
   * Check if the node has a published version.
   *
   * @return bool
   *   TRUE, if the default revision is published.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function hasPublishedVersion() {
    $revision = $this->getCurrentRevision();
    if (NULL === $revision) {
      return FALSE;
    }

    return $revision->isPublished();
  }

  /**
   * Return the title for the actions.
   *
   * @return array
   *   The title render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getMetaTitle() {
    return [
      '#prefix' => '<h1>',
      '#suffix' => '</h1>',
      '#markup' => $this->hasPublishedVersion() ? $this->t('This content has a published version') : $this->t('This content has no published version'),
      '#weight' => 0,
    ];
  }

  /**
   * Return the current revision, if possible.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The latest published revision, or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getCurrentRevision() {
    if ($this->node()->isNew()) {
      return NULL;
    }

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $data = $nodeStorage->load($this->node()->id());

    if (empty($data)) {
      return NULL;
    }
    return $data;
  }

  /**
   * Get the last published date for the node.
   *
   * @return array
   *   The date as array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getLastPublishDate() {
    $lastPublishDate = $this->t('Not available');
    if ($this->node()->isNew()) {
      $lastPublishDate = $this->t('Not saved yet');
    }
    else {
      $revision = $this->nodeRevisionManager->loadLatestPublishedRevision($this->node());
      if (NULL !== $revision) {
        $lastPublishDate = $this->dateFormatter->format($revision->getChangedTime(), 'tieto_date');
      }
    }

    return [
      '#type' => 'container',
      '#weight' => 0,
      '#attributes' => [
        'class' => [
          'node-meta--last-publish-date',
        ],
      ],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'label',
        '#attributes' => [
          'class' => [
            'actions-info-label',
            'actions-info--last-publish-date--label',
          ],
        ],
        '#value' => $this->t('Last publish date'),
      ],
      'value' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => [
            'actions-info-value',
            'actions-info--last-publish-date--value',
          ],
        ],
        '#value' => $lastPublishDate,
      ],
    ];
  }

  /**
   * Get the first published date for the node.
   *
   * @return array
   *   The date as array.
   */
  protected function getFirstPublishDate() {
    $firstPublishValue = $this->node()->get('field_first_publish_date')->getValue();
    // TODO: review why can $firstPublishValueItem just 0 value.
    // (hint: I created a new node, saved, than I edited and saved again, and
    // still 0.)
    $firstPublishValue = \reset($firstPublishValue);
    $firstPublishDate = $this->t('Not available');
    if (isset($firstPublishValue['value'])) {
      $firstPublishValue = \strtotime($firstPublishValue['value']);
      $firstPublishDate = $this->dateFormatter->format($firstPublishValue, 'tieto_date');
    }

    return [
      '#type' => 'container',
      '#weight' => 5,
      '#attributes' => [
        'class' => [
          'node-meta--first-publish-date',
        ],
      ],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'label',
        '#attributes' => [
          'class' => [
            'actions-info-label',
            'actions-info--first-publish-date--label',
          ],
        ],
        '#value' => $this->t('First publish date'),
      ],
      'value' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => [
            'actions-info-value',
            'actions-info--first-publish-date--value',
          ],
        ],
        '#value' => $firstPublishDate,
      ],
    ];
  }

  /**
   * Return the prev version for the actions.
   *
   * @return array
   *   The last updated render array.
   */
  protected function getInfoPrevVersions() {
    if (!$this->nodeRevisionManager->hasRevisions($this->node())) {
      return [];
    }

    return [
      '#type' => 'container',
      '#weight' => 2,
      '#attributes' => [
        'class' => [
          'actions-info--last-update',
        ],
      ],
      'content' => Link::createFromRoute(
        'Previous versions',
        'entity.node.version_history',
        ['node' => $this->node()->id()],
        [
          'attributes' => [
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
          ],
        ]
      )->toRenderable(),
    ];
  }

  /**
   * Return the last updated for the actions.
   *
   * @return array
   *   The last updated render array.
   */
  protected function getInfoLastUpdated() {
    return [
      '#type' => 'container',
      '#weight' => 1,
      '#attributes' => [
        'class' => [
          'actions-info--last-update',
        ],
      ],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'label',
        '#attributes' => [
          'class' => [
            'actions-info-label',
            'actions-info--last-update--label',
          ],
        ],
        '#value' => $this->t('Last update'),
      ],
      'value' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => [
            'actions-info-value',
            'actions-info--last-update--value',
          ],
        ],
        '#value' => $this->dateFormatter->format($this->node()->getChangedTime(), 'tieto_date'),
      ],
    ];
  }

  /**
   * Return the title for the actions.
   *
   * @return array
   *   The title render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getInfoTitle() {
    if ($this->node()->isNew()) {
      $titleText = $this->t('The content above is not been saved yet.');
    }
    else {
      $titleText = $this->t('Unknown');
      $nodeModerationStates = $this->getNodeModerationStates();
      $latest = $nodeModerationStates['latest'];
      $default = $nodeModerationStates['default'];

      if (!empty($latest) && !empty($default)) {
        if ($latest['machine_name'] === $default['machine_name']) {
          $titleText = $this->t('The content above is @latest_status', [
            '@latest_status' => $latest['label'],
          ]);
        }
        else {
          $titleText = $this->t('The content above is @latest_status (different than the @default_status)', [
            '@latest_status' => $latest['label'],
            '@default_status' => $default['label'],
          ]);
        }
      }
      elseif (!empty($latest)) {
        $titleText = $this->t('The content above is @latest_status', [
          '@latest_status' => $latest['label'],
        ]);
      }
      elseif (!empty($default)) {
        $titleText = $this->t('The content above is @default_status', [
          '@default_status' => $default['label'],
        ]);
      }
    }

    return [
      '#type' => 'container',
      '#weight' => 0,
      '#attributes' => [
        'class' => [
          'actions-info--title',
        ],
      ],
      'content' => [
        '#prefix' => '<h1>',
        '#suffix' => '</h1>',
        '#markup' => $titleText,
      ],
    ];
  }

  /**
   * Returns save button description.
   *
   * @return string
   *   The button description.
   */
  protected function getSaveDescription() {
    $date = $this->getScheduledDate('publish');

    if ($date) {
      return $this->t('Scheduled publish date: <span>@date</span>', [
        '@date' => $this->dateFormatter->format($date, 'tieto_date'),
      ]);
    }

    return $this->t('Will not publish');
  }

  /**
   * Returns archive button description.
   *
   * @return string
   *   The button description.
   */
  protected function getArchiveDescription() {
    $date = $this->getScheduledDate('trash');

    if ($date) {
      return $this->t('Scheduled archive date: <span>@date</span>', [
        '@date' => $this->dateFormatter->format($date, 'tieto_date'),
      ]);
    }

    return '';
  }

  /**
   * Returns archive button description.
   *
   * @return string
   *   The button description.
   */
  protected function getUnpublishDescription() {
    $date = $this->getScheduledDate('unpublish');

    if ($date) {
      return $this->t('Scheduled unpublish date: <span>@date</span>', [
        '@date' => $this->dateFormatter->format($date, 'tieto_date'),
      ]);
    }

    return '';
  }

  /**
   * Returns scheduled date by type.
   *
   * @param string $type
   *   The scheduled date type.
   *
   * @return string
   *   The date timestamp.
   */
  protected function getScheduledDate(string $type = 'publish') {
    $fieldName = "scheduled_{$type}_date";

    $scheduledDateField = $this->node()->{$fieldName};

    if (!$scheduledDateField->isEmpty() && ($updateTimestamp = $scheduledDateField->entity->update_timestamp)) {
      $scheduledDate = $updateTimestamp
        ->first()
        ->getValue();

      if (\Drupal::time()->getRequestTime() < $scheduledDate['value']) {
        return $scheduledDate['value'];
      }
    }

    return NULL;
  }

  /**
   * Return "Actions" metadata.
   *
   * @return array
   *   The actions array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getActions() {
    return [
      'moderation_state_unpublished' => [
        'enable_notifications' => FALSE,
        'title' => $this->t('Save'),
        'hyphenated_key' => 'moderation-state-unpublished',
        'scheduled' => NULL !== $this->getScheduledDate('publish'),
        'description' => $this->getSaveDescription(),
        'field' => 'scheduled_unpublish_date',
      ],
      'preview' => [],
      'moderation_state_trash' => [
        'enable_notifications' => TRUE,
        'title' => $this->t('Archive'),
        'hyphenated_key' => 'moderation-state-trash',
        'scheduled' => NULL !== $this->getScheduledDate('trash'),
        'description' => $this->getArchiveDescription(),
        'field' => 'scheduled_trash_date',
      ],
      'delete' => [],
      'moderation_state_published' => [
        'enable_notifications' => TRUE,
        'title' => $this->t('Publish'),
        'hyphenated_key' => 'moderation-state-published',
        'scheduled' => NULL !== $this->getScheduledDate('publish'),
        'description' => $this->hasPublishedVersion() ? $this->t('Replaces the published version') : '',
        'field' => 'scheduled_publish_date',
      ],
      'moderation_state_unpublished_content' => [
        'enable_notifications' => TRUE,
        'title' => $this->t('Unpublish'),
        'hyphenated_key' => 'moderation-state-unpublished-content',
        'scheduled' => NULL !== $this->getScheduledDate('unpublish'),
        'description' => $this->getUnpublishDescription(),
        'field' => 'scheduled_unpublish_date',
      ],
    ];
  }

  /**
   * Return the notification action buttons.
   *
   * @return array
   *   The button render arrays.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getNotificationActions() {
    $notifications = [];
    $actions = $this->getActions();
    foreach ($actions as $key => $action) {
      if (!isset($action['enable_notifications']) || $action['enable_notifications'] === FALSE) {
        continue;
      }
      if ($key === 'moderation_state_published') {
        $action['title'] = $this->t('Publish now');
      }

      $notifications[$key] = [
        '#type' => 'button',
        '#name' => 'moderation-state-notification-button-' . $action['hyphenated_key'],
        '#id' => 'moderation-state-notification-button-' . $action['hyphenated_key'],
        '#value' => $action['title'],
        '#attributes' => [
          'class' => [
            'use-ajax-submit',
            'button',
            'moderation-state-notification-button',
          ],
          'data-moderation-state' => $key,
        ],
        '#ajax' => [
          'callback' => [NotificationsHelper::class, 'notificationCallback'],
        ],
        '#submit' => [],
        '#limit_validation_errors' => [],
        '#displayNotification' => $this->displayNotificationAction($action['field']),
      ];
    }

    return $notifications;
  }

  /**
   * Determine if we should display an action.
   *
   * @param string $field
   *   The field name.
   *
   * @return bool
   *   Whether we should display it, or not.
   */
  protected function displayNotificationAction($field) {
    $scheduleState = $this->form[$field]['widget']['entities'];
    if (isset($scheduleState['0']['#label'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determine if the node can be re-published or not.
   *
   * @return bool
   *   TRUE if it can be re-published.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function canBeRepublished() {
    $revision = $this->nodeRevisionManager->loadLatestPublishedRevision($this->node());
    if (NULL === $revision) {
      return FALSE;
    }

    return !$this->node()->isPublished();
  }

  /**
   * Return the node view link.
   *
   * @return array
   *   Empty array if the node is new, or the view link array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getNodeViewLink() {
    if ($this->node()->isNew()) {
      return [];
    }

    // @todo: Fixme/refactor.
    if ($this->node()->isPublished()) {
      $label = $this->t('See the published version');
    }
    elseif ($this->hasPublishedVersion()) {
      $label = $this->t('See the last published version');
    }
    elseif ($this->canBeRepublished()) {
      $label = $this->t('See the last published version');
    }
    else {
      return [];
    }

    $url = Url::fromRoute(
      'entity.node.canonical',
      [
        'node' => $this->node()->id(),
      ],
      [
        'absolute' => TRUE,
        'attributes' => [
          'target' => '_blank',
          'rel' => 'noopener noreferrer',
        ],
      ]
    );
    if ($this->canBeRepublished()) {
      $revision = $this->nodeRevisionManager->loadLatestPublishedRevision($this->node());
      if (NULL !== $revision) {
        $url = Url::fromRoute(
          'entity.node.revision',
          [
            'node' => $revision->id(),
            'node_revision' => $revision->getRevisionId(),
          ],
          [
            'absolute' => TRUE,
            'attributes' => [
              'target' => '_blank',
              'rel' => 'noopener noreferrer',
            ],
          ]
        );
      }
    }

    $link = Link::fromTextAndUrl(
      $url->toString(TRUE)->getGeneratedUrl(),
      $url
    )->toString()->getGeneratedLink();

    $build = [
      '#type' => 'container',
      '#weight' => 15,
      '#attributes' => [
        'class' => [
          'node-meta--view-link',
        ],
      ],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'label',
        '#attributes' => [
          'class' => [
            'node-meta-label',
            'node-meta--view-link--label',
          ],
        ],
        '#value' => $label,
      ],
      'value' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => [
            'node-meta-value',
            'node-meta--view-link--value',
          ],
        ],
        '#value' => $link,
      ],
    ];

    return $build;
  }

}
