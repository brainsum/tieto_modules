<?php

namespace Drupal\tieto_lifecycle_management\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Class SchedulingManager.
 *
 * @package Drupal\tieto_lifecycle_management\Service
 */
final class SchedulingManager {

  /**
   * Storage for Scheduled Update entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $scheduleStorage;

  /**
   * Config for the tieto_lifecycle_management module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $lifeCycleConfig;

  /**
   * SchedulingManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->lifeCycleConfig = $configFactory->get('tieto_lifecycle_management.settings');

    $this->scheduleStorage = $entityTypeManager->getStorage('scheduled_update');
  }

  /**
   * Set default scheduling for the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function setDefaultsForEntity(EntityInterface $entity): void {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    if (!($entity instanceof FieldableEntityInterface)) {
      return;
    }

    if (!$this->publishingTimeChanged($entity)) {
      return;
    }

    // @todo: Only update, if there's a last published date.
    // @todo: Update, when the last published date is updated.
    foreach ($this->loadSchedulingDefaults($entity) as $fieldName => $fieldSettings) {
      $fieldSettings['name'] = $fieldName;
      $this->setSchedulingDefault($entity, $fieldSettings);
    }
  }

  /**
   * Load defaults for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The loaded defaults.
   */
  private function loadSchedulingDefaults(EntityInterface $entity): array {
    return $this->lifeCycleConfig->get('fields')[$entity->getEntityTypeId()][$entity->bundle()] ?? [];
  }

  /**
   * Check if the entity publishing time changed.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE, if it changed.
   */
  private function publishingTimeChanged(FieldableEntityInterface $entity): bool {
    return $this->isPublished($entity);
  }

  /**
   * Check if an entity is published or not.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE, if it's published.
   *
   * @todo: Make this work with non-nodes, too.
   */
  private function isPublished(EntityInterface $entity): bool {
    // @todo: Maybe check moderation status.
    if ($entity instanceof EntityPublishedInterface) {
      return $entity->isPublished();
    }

    // @todo: Maybe throw error.
    return FALSE;
  }

  /**
   * Set a scheduling default.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param array $fieldSettings
   *   Settings for the field.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  private function setSchedulingDefault(
    FieldableEntityInterface $entity,
    array $fieldSettings
  ): void {
    /** @var string $fieldName */
    $fieldName = $fieldSettings['name'];
    /** @var string $dateDefault */
    $dateDefault = $fieldSettings['date'];
    if (empty($dateDefault)) {
      return;
    }

    if (!$entity->hasField($fieldName)) {
      return;
    }

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $scheduleField */
    $scheduleField = $entity->get($fieldName);

    if ($scheduleField->isEmpty()) {
      $newDate = (new DrupalDateTime())
        ->add(\DateInterval::createFromDateString($dateDefault));

      /** @var string $targetSchedule */
      $targetSchedule = \reset($scheduleField->getSetting('handler_settings')['target_bundles']);

      /** @var \Drupal\scheduled_updates\ScheduledUpdateInterface $newSchedule */
      $newSchedule = $this->scheduleStorage->create([
        'type' => $targetSchedule,
        'update_timestamp' => $newDate->getTimestamp(),
      ]);
      $newSchedule->save();

      $entity->get($fieldName)->setValue($newSchedule);
    }
  }

}
