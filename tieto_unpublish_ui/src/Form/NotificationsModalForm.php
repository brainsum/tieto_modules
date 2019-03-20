<?php

namespace Drupal\tieto_unpublish_ui\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class NotificationsModalForm.
 *
 * @package Drupal\tieto_unpublish_ui\Form
 */
class NotificationsModalForm {

  /**
   * Build the modal form while the parent form and state are available.
   *
   * @param array $parentForm
   *   The parent form.
   * @param \Drupal\Core\Form\FormStateInterface $parentFormState
   *   The parent form state.
   * @param string $moderationState
   *   The pressed moderation state button.
   *
   * @return array
   *   The render array.
   */
  public static function buildFromParent(array &$parentForm, FormStateInterface $parentFormState, $moderationState) {
    /** @var \Drupal\node\NodeForm $formObject */
    $formObject = $parentFormState->getFormObject();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $formObject->getEntity();

    $modalForm = [];
    $modalForm['#type'] = 'form';
    $modalForm['#id'] = 'tieto-action-notification-modal-form';

    $stateMapping = static::getStateMappings()[$moderationState] ?? NULL;
    if (NULL === $stateMapping) {
      $modalForm['information'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'modal-information',
          ],
        ],
        'content' => [
          '#markup' => t('It seems an error occurred. Please try again later.'),
        ],
      ];
    }
    else {
      $scheduleState = $parentForm[$stateMapping['field']]['widget']['entities'];
      $scheduledLabel = $scheduleState['0']['#label'];

      /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $scheduleField */
      $scheduleField = $node->get($stateMapping['field']);
      if (empty($scheduledLabel)) {
        $modalForm['information'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'modal-information',
            ],
          ],
          'content' => [
            '#markup' => t('There are no scheduled "@state" updates added for this content.', [
              '@state' => $stateMapping['label'],
            ]),
          ],
        ];
        $stateMapping = NULL;
      }
      else {
        $modalForm['information'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'modal-information',
            ],
          ],
          'content' => [
            '#markup' => t('Would you like to <b>override the @type</b> (@date) and @state the content now?', [
              '@type' => $scheduleField->getFieldDefinition()->getLabel(),
              '@date' => $scheduledLabel,
              '@state' => $stateMapping['label'] ?? t('Save'),
            ]),
          ],
        ];
      }
    }

    $hyphenatedState = \str_replace('_', '-', $moderationState);
    $modalForm['moderation_state'] = [
      '#type' => 'hidden',
      '#value' => $hyphenatedState,
      '#id' => 'modal-moderation-state-information',
      '#attributes' => [
        'id' => 'modal-moderation-state-information',
        'class' => [
          'hidden',
        ],
        'data-value' => $hyphenatedState,
      ],
    ];

    $modalForm['actions'] = [
      '#type' => 'container',
    ];

    if (NULL !== $stateMapping) {
      $modalForm['actions']['submit'] = [
        '#type' => 'button',
        '#name' => 'modal-submit-button',
        '#id' => 'modal-submit-button',
        '#attributes' => [
          'id' => 'modal-submit-button',
        ],
        '#value' => ($stateMapping['label'] ?? t('Save')) . ' ' . t('now'),
      ];
    }

    $modalForm['actions']['cancel'] = [
      '#type' => 'button',
      '#name' => 'modal-cancel-button',
      '#id' => 'modal-cancel-button',
      '#attributes' => [
        'id' => 'modal-cancel-button',
      ],
      '#value' => t('Cancel'),
    ];

    return $modalForm;
  }

  /**
   * Return the labels.
   *
   * @return array
   *   Assoc array with state=>label pairs.
   */
  public static function getStateMappings() {
    return [
      'published' => [
        'label' => t('Publish'),
        'field' => 'scheduled_publish_date',
      ],
      'unpublished_content' => [
        'label' => t('Unpublish'),
        'field' => 'scheduled_unpublish_date',
      ],
      'trash' => [
        'label' => t('Archive'),
        'field' => 'scheduled_trash_date',
      ],
      'delete' => [
        'label' => t('Delete'),
        'field' => 'scheduled_delete_date',
      ],
    ];
  }

}
