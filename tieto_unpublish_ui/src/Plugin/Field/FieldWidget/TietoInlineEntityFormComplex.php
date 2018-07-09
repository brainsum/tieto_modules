<?php

namespace Drupal\tieto_unpublish_ui\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;
use Drupal\inline_entity_form\Element\InlineEntityForm;

/**
 * Complex inline widget.
 *
 * @FieldWidget(
 *   id = "tieto_inline_entity_form_complex",
 *   label = @Translation("Tieto Inline entity form - Complex"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = true
 * )
 */
class TietoInlineEntityFormComplex extends InlineEntityFormComplex {

  /**
   * Returns the options for the match operator.
   *
   * @return array
   *   List of options.
   */
  protected function getMatchOperatorOptions() {
    return [
      'STARTS_WITH' => $this->t('Starts with'),
      'CONTAINS' => $this->t('Contains'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $targetType = $this->getFieldSetting('target_type');
    // Get the entity type labels for the UI strings.
    $labels = $this->getEntityTypeLabels();

    // Build a parents array for this element's values in the form.
    $parents = \array_merge($element['#field_parents'], [
      $items->getName(),
      'form',
    ]);

    // Assign a unique identifier to each IEF widget.
    // Since $parents can get quite long, \sha1() ensures that every id has
    // a consistent and relatively short length while maintaining uniqueness.
    $this->setIefId(\sha1(\implode('-', $parents)));

    // Get the langcode of the parent entity.
    $parentLangcode = $items->getEntity()->language()->getId();

    // Determine the wrapper ID for the entire element.
    $wrapper = 'inline-entity-form-' . $this->getIefId();

    $element = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#description' => $this->fieldDefinition->getDescription(),
      '#prefix' => '<div id="' . $wrapper . '">',
      '#suffix' => '</div>',
      '#ief_id' => $this->getIefId(),
      '#ief_root' => TRUE,
      '#translating' => $this->isTranslating($form_state),
      '#field_title' => $this->fieldDefinition->getLabel(),
      '#after_build' => [
        [static::class, 'removeTranslatabilityClue'],
      ],
    ] + $element;

    $element['#attached']['library'][] = 'inline_entity_form/widget';

    $this->prepareFormState($form_state, $items, $element['#translating']);
    $entities = $form_state->get([
      'inline_entity_form',
      $this->getIefId(),
      'entities',
    ]);

    // Build the "Multiple value" widget.
    // TODO - does this belong in #element_validate?
    $element['#element_validate'][] = [static::class, 'updateRowWeights'];
    $element['#element_validate'][] = [static::class, 'validateElementDate'];
    // Add the required element marker & validation.
    if ($element['#required']) {
      $element['#element_validate'][] = [static::class, 'requiredField'];
    }

    $element['entities'] = [
      '#tree' => TRUE,
      '#theme' => 'inline_entity_form_entity_table',
      '#entity_type' => $targetType,
    ];

    // Get the fields that should be displayed in the table.
    $targetBundles = $this->getTargetBundles();
    $fields = $this->inlineFormHandler->getTableFields($targetBundles);
    $context = [
      'parent_entity_type' => $this->fieldDefinition->getTargetEntityTypeId(),
      'parent_bundle' => $this->fieldDefinition->getTargetBundle(),
      'field_name' => $this->fieldDefinition->getName(),
      'entity_type' => $targetType,
      'allowed_bundles' => $targetBundles,
    ];
    $this->moduleHandler->alter('inline_entity_form_table_fields', $fields, $context);
    $element['entities']['#table_fields'] = $fields;

    $weightDelta = \max(\ceil(\count($entities) * 1.2), 50);
    foreach ($entities as $key => $value) {
      // Data used by theme_inline_entity_form_entity_table().
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $value['entity'];
      $element['entities'][$key]['#label'] = $this->inlineFormHandler->getEntityLabel($value['entity']);
      $element['entities'][$key]['#entity'] = $value['entity'];
      $element['entities'][$key]['#needs_save'] = $value['needs_save'];

      // Handle row weights.
      $element['entities'][$key]['#weight'] = $value['weight'];

      // First check to see if this entity should be displayed as a form.
      if (!empty($value['form'])) {
        $element['entities'][$key]['title'] = [];
        $element['entities'][$key]['delta'] = [
          '#type' => 'value',
          '#value' => $value['weight'],
        ];

        // Add the appropriate form.
        if ($value['form'] === 'edit') {
          $element['entities'][$key]['form'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['ief-form', 'ief-form-row']],
            'inline_entity_form' => $this->getInlineEntityForm(
              $value['form'],
              $entity->bundle(),
              $parentLangcode,
              $key,
              \array_merge($parents, [
                'inline_entity_form',
                'entities',
                $key,
                'form',
              ]),
              $entity
            ),
          ];

          $element['entities'][$key]['form']['inline_entity_form']['#process'] = [
            [InlineEntityForm::class, 'processEntityForm'],
            [static::class, 'addIefSubmitCallbacks'],
            [static::class, 'buildEntityFormActions'],
            'tieto_unpublish_ui_ief_process',
          ];
        }
        elseif ($value['form'] === 'remove') {
          $element['entities'][$key]['form'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['ief-form', 'ief-form-row']],
            // Used by Field API and controller methods to find the relevant
            // values in $form_state.
            '#parents' => \array_merge($parents, ['entities', $key, 'form']),
            // Store the entity on the form, later modified in the controller.
            '#entity' => $entity,
            // Identifies the IEF widget to which the form belongs.
            '#ief_id' => $this->getIefId(),
            // Identifies the table row to which the form belongs.
            '#ief_row_delta' => $key,
          ];
          $this->buildRemoveForm($element['entities'][$key]['form']);
        }
      }
      else {
        $row = &$element['entities'][$key];
        $row['title'] = [];
        $row['delta'] = [
          '#type' => 'weight',
          '#delta' => $weightDelta,
          '#default_value' => $value['weight'],
          '#attributes' => ['class' => ['ief-entity-delta']],
        ];
        // Add an actions container with edit and delete buttons for the entity.
        $row['actions'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['ief-entity-operations']],
        ];

        // Make sure entity_access is not checked for unsaved entities.
        $entityId = $entity->id();
        if (empty($entityId) || $entity->access('update')) {
          $row['actions']['ief_entity_edit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Edit'),
            '#name' => 'ief-' . $this->getIefId() . '-entity-edit-' . $key,
            '#limit_validation_errors' => [],
            '#ajax' => [
              'callback' => 'inline_entity_form_get_element',
              'wrapper' => $wrapper,
            ],
            '#submit' => ['inline_entity_form_open_row_form'],
            '#ief_row_delta' => $key,
            '#ief_row_form' => 'edit',
          ];
        }

        // If 'allow_existing' is on, the default removal operation is unlink
        // and the access check for deleting happens inside the controller
        // removeForm() method.
        if (empty($entityId) || $settings['allow_existing'] || $entity->access('delete')) {
          $row['actions']['ief_entity_remove'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => 'ief-' . $this->getIefId() . '-entity-remove-' . $key,
            '#limit_validation_errors' => [],
            '#ajax' => [
              'callback' => 'inline_entity_form_get_element',
              'wrapper' => $wrapper,
            ],
            '#submit' => ['inline_entity_form_open_row_form'],
            '#ief_row_delta' => $key,
            '#ief_row_form' => 'remove',
            '#access' => !$element['#translating'],
          ];
        }
      }
    }

    // When in translation, the widget only supports editing (translating)
    // already added entities, so there's no need to show the rest.
    if ($element['#translating']) {
      if (empty($entities)) {
        // There are no entities available for translation, hide the widget.
        $element['#access'] = FALSE;
      }
      return $element;
    }

    $entitiesCount = \count($entities);
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    if ($cardinality > 1) {
      // Add a visual cue of cardinality count.
      $message = $this->t('You have added @entities_count out of @cardinality_count allowed @label.', [
        '@entities_count' => $entitiesCount,
        '@cardinality_count' => $cardinality,
        '@label' => $labels['plural'],
      ]);
      $element['cardinality_count'] = [
        '#markup' => '<div class="ief-cardinality-count">' . $message . '</div>',
      ];
    }
    // Do not return the rest of the form if cardinality count has been reached.
    if ($cardinality > 0 && $entitiesCount === $cardinality) {
      return $element;
    }

    $createBundles = $this->getCreateBundles();
    $createBundlesCount = \count($createBundles);
    $allowNew = $settings['allow_new'] && !empty($createBundles);
    $hideCancel = FALSE;
    // If the field is required and empty try to open one of the forms.
    if (empty($entities) && $this->fieldDefinition->isRequired()) {
      if (!$allowNew && $settings['allow_existing']) {
        $form_state->set(['inline_entity_form', $this->getIefId(), 'form'], 'ief_add_existing');
        $hideCancel = TRUE;
      }
      elseif ($createBundlesCount === 1 && $allowNew && !$settings['allow_existing']) {
        $bundle = \reset($targetBundles);

        // The parent entity type and bundle must not be the same as the inline
        // entity type and bundle, to prevent recursion.
        $parentEntityType = $this->fieldDefinition->getTargetEntityTypeId();
        $parentBundle = $this->fieldDefinition->getTargetBundle();
        if ($parentEntityType !== $targetType || $parentBundle !== $bundle) {
          $form_state->set(['inline_entity_form', $this->getIefId(), 'form'], 'add');
          $form_state->set([
            'inline_entity_form',
            $this->getIefId(),
            'form settings',
          ], [
            'bundle' => $bundle,
          ]);
          $hideCancel = TRUE;
        }
      }
    }

    // If no form is open, show buttons that open one.
    $openForm = $form_state->get([
      'inline_entity_form',
      $this->getIefId(),
      'form',
    ]);

    if (empty($openForm)) {
      $element['actions'] = [
        '#attributes' => ['class' => ['container-inline']],
        '#type' => 'container',
        '#weight' => 100,
      ];

      // The user is allowed to create an entity of at least one bundle.
      if ($allowNew) {
        // Let the user select the bundle, if multiple are available.
        if ($createBundlesCount > 1) {
          $bundles = [];
          foreach ($this->entityTypeBundleInfo->getBundleInfo($targetType) as $bundleName => $bundleInfo) {
            if (\in_array($bundleName, $createBundles, TRUE)) {
              $bundles[$bundleName] = $bundleInfo['label'];
            }
          }
          \asort($bundles);

          $element['actions']['bundle'] = [
            '#type' => 'select',
            '#options' => $bundles,
          ];
        }
        else {
          $element['actions']['bundle'] = [
            '#type' => 'value',
            '#value' => \reset($createBundles),
          ];
        }

        $element['actions']['ief_add'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add new @type_singular', ['@type_singular' => $labels['singular']]),
          '#name' => 'ief-' . $this->getIefId() . '-add',
          '#limit_validation_errors' => [\array_merge($parents, ['actions'])],
          '#ajax' => [
            'callback' => 'inline_entity_form_get_element',
            'wrapper' => $wrapper,
          ],
          '#submit' => ['inline_entity_form_open_form'],
          '#ief_form' => 'add',
        ];
      }

      if ($settings['allow_existing']) {
        $element['actions']['ief_add_existing'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add existing @type_singular', ['@type_singular' => $labels['singular']]),
          '#name' => 'ief-' . $this->getIefId() . '-add-existing',
          '#limit_validation_errors' => [\array_merge($parents, ['actions'])],
          '#ajax' => [
            'callback' => 'inline_entity_form_get_element',
            'wrapper' => $wrapper,
          ],
          '#submit' => ['inline_entity_form_open_form'],
          '#ief_form' => 'ief_add_existing',
        ];
      }
    }
    else {
      // There's a form open, show it.
      if ($form_state->get(['inline_entity_form', $this->getIefId(), 'form']) === 'add') {
        $element['form'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['ief-form', 'ief-form-bottom']],
          'inline_entity_form' => $this->getInlineEntityForm(
            'add',
            $this->determineBundle($form_state),
            $parentLangcode,
            NULL,
            \array_merge($parents, ['inline_entity_form'])
          ),
        ];
        $element['form']['inline_entity_form']['#process'] = [
          [InlineEntityForm::class, 'processEntityForm'],
          [static::class, 'addIefSubmitCallbacks'],
          [static::class, 'buildEntityFormActions'],
          'tieto_unpublish_ui_ief_process',
        ];
      }
      elseif ($form_state->get(['inline_entity_form', $this->getIefId(), 'form']) === 'ief_add_existing') {
        $element['form'] = [
          '#type' => 'fieldset',
          '#attributes' => ['class' => ['ief-form', 'ief-form-bottom']],
          // Identifies the IEF widget to which the form belongs.
          '#ief_id' => $this->getIefId(),
          // Used by Field API and controller methods to find the relevant
          // values in $form_state.
          '#parents' => \array_merge($parents),
          // Pass the current entity type.
          '#entity_type' => $targetType,
          // Pass the widget specific labels.
          '#ief_labels' => $this->getEntityTypeLabels(),
        ];

        $element['form'] += inline_entity_form_reference_form($element['form'], $form_state);
      }

      // Pre-opened forms can't be closed in order to force the user to
      // add / reference an entity.
      if ($hideCancel) {
        if ($openForm === 'add') {
          $processElement = &$element['form']['inline_entity_form'];
        }
        elseif ($openForm === 'ief_add_existing') {
          $processElement = &$element['form'];
        }
        $processElement['#process'][] = [static::class, 'hideCancel'];
      }
    }

    return $element;
  }

  /**
   * Builds remove form.
   *
   * @param mixed $form
   *   Form array structure.
   */
  protected function buildRemoveForm(&$form) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form['#entity'];
    $entityId = $entity->id();
    $entityLabel = $this->inlineFormHandler->getEntityLabel($entity);
    $labels = $this->getEntityTypeLabels();

    if ($entityLabel) {
      $message = $this->t('Are you sure you want to remove %label?', ['%label' => $entityLabel]);
    }
    else {
      $message = $this->t('Are you sure you want to remove this %entity_type?', ['%entity_type' => $labels['singular']]);
    }

    $form['message'] = [
      '#theme_wrappers' => ['container'],
      '#markup' => $message,
    ];

    if (!empty($entityId) && $this->getSetting('allow_existing') && $entity->access('delete')) {
      $form['delete'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Delete this @type_singular from the system.', ['@type_singular' => $labels['singular']]),
      ];
    }

    // Build a deta suffix that's appended to button #name keys for uniqueness.
    $delta = $form['#ief_id'] . '-' . $form['#ief_row_delta'];

    // Add actions to the form.
    $form['actions'] = [
      '#type' => 'container',
      '#weight' => 100,
    ];
    $form['actions']['ief_remove_confirm'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove'),
      '#name' => 'ief-remove-confirm-' . $delta,
      '#limit_validation_errors' => [$form['#parents']],
      '#ajax' => [
        'callback' => 'inline_entity_form_get_element',
        'wrapper' => 'inline-entity-form-' . $form['#ief_id'],
      ],
      '#allow_existing' => $this->getSetting('allow_existing'),
      '#submit' => [[static::class, 'submitConfirmRemove']],
      '#ief_row_delta' => $form['#ief_row_delta'],
    ];
    $form['actions']['ief_remove_cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#name' => 'ief-remove-cancel-' . $delta,
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => 'inline_entity_form_get_element',
        'wrapper' => 'inline-entity-form-' . $form['#ief_id'],
      ],
      '#submit' => [[static::class, 'submitCloseRow']],
      '#ief_row_delta' => $form['#ief_row_delta'],
    ];

    $form['#process'][] = 'tieto_unpublish_ui_ief_process';
  }

  /**
   * Determines bundle to be used when creating entity.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return string
   *   Bundle machine name.
   *
   * @TODO - Figure out if can be simplified.
   */
  protected function determineBundle(FormStateInterface $form_state) {
    $iefSettings = $form_state->get(['inline_entity_form', $this->getIefId()]);
    if (!empty($iefSettings['form settings']['bundle'])) {
      return $iefSettings['form settings']['bundle'];
    }
    if (!empty($iefSettings['bundle'])) {
      return $iefSettings['bundle'];
    }

    $targetBundles = $this->getTargetBundles();
    return \reset($targetBundles);
  }

  /**
   * An #element_validate callback for validating a scheduled date.
   */
  public static function validateElementDate($element, FormStateInterface $formState) {
    $iefId = $element['#ief_id'];
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $formState->get(['inline_entity_form', $iefId, 'instance']);
    if (!static::isScheduleField($field)) {
      return;
    }
    $updateTimestamp = static::getScheduledUpdateTimestamp($iefId, $formState);
    if (NULL === $updateTimestamp) {
      return;
    }

    $currentTime = \Drupal::time()->getCurrentTime();
    if ($updateTimestamp < $currentTime) {
      $formState->setError($element, t('Scheduling in the past is not possible. Please, choose a date in the future for the @name', [
        '@name' => $field->getLabel(),
      ]));
    }
  }

  /**
   * Validate callback for the AJAX form "save" button.
   */
  public static function validateFormDate(array &$form, FormStateInterface $formState) {
    $trigger = $formState->getTriggeringElement();
    if (!isset($trigger['#ief_submit_trigger']) || FALSE === $trigger['#ief_submit_trigger']) {
      return;
    }
    $iefId = \str_replace('inline-entity-form-', '', $trigger['#ajax']['wrapper']);
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $formState->get(['inline_entity_form', $iefId, 'instance']);
    if (!static::isScheduleField($field)) {
      return;
    }
    $updateTimestamp = static::getScheduledUpdateTimestamp($iefId, $formState);
    if (NULL === $updateTimestamp) {
      return;
    }

    $currentTime = \Drupal::time()->getCurrentTime();
    if ($updateTimestamp < $currentTime) {
      if (isset($form[$field->getName()]['widget']['form'])) {
        $formState->setError($form[$field->getName()]['widget']['form']['inline_entity_form']['update_timestamp']['widget']['0']['value'], t('Scheduling in the past is not possible. Please, choose a date in the future for the @name', [
          '@name' => $field->getLabel(),
        ]));
      }
      else {
        $formState->setError($form[$field->getName()]['widget']['entities'][0]['form']['inline_entity_form']['update_timestamp']['widget'][0]['value'], t('Scheduling in the past is not possible. Please, choose a date in the future for the @name', [
          '@name' => $field->getLabel(),
        ]));
      }
    }
  }

  /**
   * Extract the scheduled update timestamp.
   *
   * @param string $iefId
   *   The ief ID.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   *
   * @return int|null
   *   The timestamp or NULL.
   */
  public static function getScheduledUpdateTimestamp($iefId, FormStateInterface $formState) {
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $formState->get(['inline_entity_form', $iefId, 'instance']);

    /** @var array $entities */
    $entities = $formState->get(['inline_entity_form', $iefId, 'entities']);
    if ($rawUpdateTimestamp = $formState->getValue([
      $field->getName(),
      'form',
      'inline_entity_form',
      'update_timestamp',
    ])) {
      if (!$rawUpdateTimestamp[0]['value'] instanceof DrupalDateTime) {
        return NULL;
      }
      /** @var \Drupal\Core\Datetime\DrupalDateTime $updateDateObject */
      $updateDateObject = \reset($rawUpdateTimestamp)['value'];
      $tmpDateObject = new \DateTime($updateDateObject->format($updateDateObject::FORMAT));
      $updateTimestamp = $tmpDateObject->getTimestamp();
    }
    elseif ($rawUpdateTimestamp = $formState->getValue([
      $field->getName(),
      'form',
      'inline_entity_form',
      'entities',
      0,
      'form',
      'update_timestamp',
    ])) {
      if (!$rawUpdateTimestamp[0]['value'] instanceof DrupalDateTime) {
        return NULL;
      }
      /** @var \Drupal\Core\Datetime\DrupalDateTime $updateDateObject */
      $updateDateObject = \reset($rawUpdateTimestamp)['value'];
      $tmpDateObject = new \DateTime($updateDateObject->format($updateDateObject::FORMAT));
      $updateTimestamp = $tmpDateObject->getTimestamp();
    }
    else {
      /** @var \Drupal\scheduled_updates\ScheduledUpdateInterface $scheduleEntity */
      $scheduleEntity = \reset($entities)['entity'];
      if (NULL === $scheduleEntity) {
        return NULL;
      }
      $updateTimestamp = (int) $scheduleEntity->get('update_timestamp')->value;
    }

    if (empty($updateTimestamp)) {
      return NULL;
    }

    return $updateTimestamp;
  }

  /**
   * Check if a field is a schedule field.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public static function isScheduleField(FieldConfigInterface $field) {
    $storageSettings = $field->getFieldStorageDefinition()->getSettings();
    return isset($storageSettings['target_type']) && 'scheduled_update' === $storageSettings['target_type'];
  }

  /**
   * {@inheritdoc}
   */
  public static function buildEntityFormActions($element) {
    $element = parent::buildEntityFormActions($element);
    $element['actions']['ief_' . $element['#op'] . '_save']['#validate'][] = [static::class, 'validateFormDate'];
    return $element;
  }

}
