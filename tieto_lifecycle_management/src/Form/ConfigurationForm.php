<?php

namespace Drupal\tieto_lifecycle_management\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigurationForm.
 *
 * @package Drupal\tieto_lifecycle_management\Form
 *
 * @see: tieto_lifecycle_management.configuration_form
 */
final class ConfigurationForm extends ConfigFormBase {

  private $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * ConfigurationForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configFactory);

    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'tieto_lifecycle_management.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tieto_lifecycle_management_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $form['#tree'] = TRUE;

    /** @var \Drupal\field\FieldConfigInterface[] $scheduledFields */
    $scheduledFields = $this
      ->entityTypeManager
      ->getStorage('field_config')
      ->loadByProperties([
        'field_type' => 'entity_reference',
      ]);

    $scheduledFields = \array_filter($scheduledFields, function ($field) {
      /** @var \Drupal\field\FieldConfigInterface $field */
      return $field->getSetting('handler') === 'default:scheduled_update';
    });

    $values = $this->config('tieto_lifecycle_management.settings')->get('fields');

    $form['fields'] = [];

    foreach ($scheduledFields as $scheduledField) {
      $targetEntityId = $scheduledField->getTargetEntityTypeId();
      $targetBundleId = $scheduledField->getTargetBundle();
      $fieldId = $scheduledField->getName();

      if (!isset($form['fields'][$targetEntityId])) {
        $form['fields'][$targetEntityId] = [
          '#type' => 'fieldset',
          '#title' => $targetEntityId,
        ];
      }

      if (!isset($form['fields'][$targetEntityId][$targetBundleId])) {
        $form['fields'][$targetEntityId][$targetBundleId] = [
          '#type' => 'fieldset',
          '#title' => $targetBundleId,
        ];
      }

      $form['fields'][$targetEntityId][$targetBundleId][$fieldId] = [
        '#type' => 'fieldset',
        '#title' => $fieldId,
        'date' => [
          '#type' => 'textfield',
          '#title' => $this->t('Date'),
          '#placeholder' => '+6 months',
          '#default_value' => $values[$targetEntityId][$targetBundleId][$fieldId]['date'] ?? '',
        ],
      ];

    }

    return $form;
  }

  /**
   * Validate a date string.
   *
   * @param string $date
   *   The date string.
   *
   * @return bool
   *   TRUE, if date is valid.
   */
  protected function isRelativeDate(string $date): bool {
    $parsedDate = \date_parse($date);

    // @todo: Disallow negative numbers.
    return !(
      $parsedDate === FALSE
      || (isset($parsedDate['error_count']) && $parsedDate['error_count'] > 0)
      || !isset($parsedDate['relative'])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    foreach ($values['fields'] as $entityType => $bundles) {
      foreach ($bundles as $bundle => $fields) {
        foreach ($fields as $field => $fieldValues) {
          $dateValue = $fieldValues['date'] ?? NULL;

          if (
            !empty($dateValue)
            && !$this->isRelativeDate($dateValue)
          ) {
            $form_state->setError($form['fields'][$entityType][$bundle][$field]['date'], $this->t('@value is not a valid relative date.', [
              '@value' => $dateValue,
            ]));
          }
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    $this->config('tieto_lifecycle_management.settings')
      ->set('fields', $values['fields'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
