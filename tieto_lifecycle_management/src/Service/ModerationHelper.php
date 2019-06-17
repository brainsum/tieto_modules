<?php

namespace Drupal\tieto_lifecycle_management\Service;

use DateInterval;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tieto_lifecycle_management\Constant\RemovalReason;
use Drupal\tieto_lifecycle_management\Event\LifeCycleIgnoreEvent;
use Drupal\tieto_lifecycle_management\Event\LifeCycleRemoveEvent;
use Drupal\tieto_lifecycle_management\Event\LifeCycleUpdateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_chunk;
use function array_keys;
use function json_encode;
use function key;

/**
 * Class ModerationHelper.
 *
 * @package Drupal\tieto_lifecycle_management\Service
 */
class ModerationHelper {

  use MessengerTrait;
  use StringTranslationTrait;

  private $entityTypeManager;

  private $time;

  private $lifeCycleConfig;

  private $logger;

  private $dateFormatter;

  private $eventDispatcher;

  /**
   * ModerationHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger channel factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   Date formatter.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   Event dispatcher.
   *
   * @todo: Add the tieto_lifecycle_management.moderation_message service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    TimeInterface $time,
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    DateFormatterInterface $dateFormatter,
    EventDispatcherInterface $dispatcher
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->time = $time;
    $this->lifeCycleConfig = $configFactory->get('tieto_lifecycle_management.settings');
    $this->logger = $loggerChannelFactory->get('tieto_lifecycle_management');
    $this->dateFormatter = $dateFormatter;

    $this->eventDispatcher = $dispatcher;
  }

  /**
   * Determines whether a transition is correct or not.
   *
   * @param string $currentState
   *   The current state.
   * @param string $targetState
   *   The desired state.
   *
   * @return bool
   *   TRUE for correct transitions, FALSE otherwise.
   *
   * @todo: temporary
   * @todo: FIXME, move to config.
   */
  public function isCorrectTransition(string $currentState, string $targetState): bool {
    switch ($currentState) {
      case 'unpublished':
        return TRUE;

      case 'published':
        return in_array($targetState, ['unpublished_content', 'trash'], TRUE);

      case 'unpublished_content':
        return $targetState === 'trash';
    }

    return FALSE;
  }

  /**
   * Return notification about new entities.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The translatable message, or NULL.
   */
  protected function newEntityNotificationMessage(FieldableEntityInterface $entity): ?TranslatableMarkup {
    if ($entity->isNew() === FALSE) {
      return NULL;
    }

    if ($entity->bundle() === 'service_alert') {
      return $this->t('Service alerts will be assigned automatic unpublish and deletion dates. These dates can be overridden by entering respective dates manually.');
    }

    return $this->t('News items will be assigned automatic unpublish and deletion dates. These dates can be overridden by entering respective dates manually.');
  }

  /**
   * Return notification about draft deletion.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The translatable message, or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function draftDeleteNotificationMessage(FieldableEntityInterface $entity): ?TranslatableMarkup {
    $deleteTime = $this->unpublishedEntityDeleteTime($entity);

    if ($deleteTime === NULL) {
      return NULL;
    }

    if ($entity->bundle() === 'service_alert') {
      return $this->t('Service alerts will be assigned automatic unpublish and deletion dates. These dates can be overridden by entering respective dates manually and by publishing the content. Otherwise, this content will be deleted on @deleteDate', [
        '@deleteDate' => $this->dateFormatter->format($deleteTime, 'tieto_date'),
      ]);
    }

    return $this->t('News items will be assigned automatic unpublish and deletion dates. These dates can be overridden by entering respective dates manually and by publishing the content. Otherwise, this content will be deleted on @deleteDate', [
      '@deleteDate' => $this->dateFormatter->format($deleteTime, 'tieto_date'),
    ]);
  }

  /**
   * Return notification about unpublishing.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The translatable message, or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function unpublishNotificationMessage(FieldableEntityInterface $entity): ?TranslatableMarkup {
    $unpublishTime = $this->entityUnpublishTime($entity);

    if ($unpublishTime === NULL) {
      return NULL;
    }

    if ($entity->bundle() === 'service_alert') {
      return $this->t('Service alerts will be assigned automatic unpublish and deletion dates. This content will be unpublished on @unpublishDate', [
        '@unpublishDate' => $this->dateFormatter->format($unpublishTime, 'tieto_date'),
      ]);
    }

    return $this->t('News items will be assigned automatic unpublish and deletion dates. These dates can be overridden by entering respective dates manually or by re-publishing the content. This article will be unpublished on @unpublishDate', [
      '@unpublishDate' => $this->dateFormatter->format($unpublishTime, 'tieto_date'),
    ]);
  }

  /**
   * Return notification about archiving.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The translatable message, or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function archiveNotificationMessage(FieldableEntityInterface $entity): ?TranslatableMarkup {
    $archiveTime = $this->entityArchiveTime($entity);

    if ($archiveTime === NULL) {
      return NULL;
    }

    if ($entity->bundle() === 'service_alert') {
      return $this->t('Service alerts will be assigned automatic unpublish and deletion dates. This content will be archived on @archiveDate', [
        '@archiveDate' => $this->dateFormatter->format($archiveTime, 'tieto_date'),
      ]);
    }

    return $this->t('News items will be assigned automatic unpublish and deletion dates. These dates can be overridden by entering respective dates manually or by re-publishing the content. This article will be archived on @archiveDate', [
      '@archiveDate' => $this->dateFormatter->format($archiveTime, 'tieto_date'),
    ]);
  }

  /**
   * Return notification about deleting old entities.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The translatable message, or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function oldDeleteNotificationMessage(FieldableEntityInterface $entity): ?TranslatableMarkup {
    $deleteTime = $this->entityDeleteTime($entity);

    if ($deleteTime === NULL) {
      return NULL;
    }

    if ($entity->bundle() === 'service_alert') {
      return $this->t('Service alerts will be assigned automatic unpublish and deletion dates. This content will be deleted on @deleteDate', [
        '@deleteDate' => $this->dateFormatter->format($deleteTime, 'tieto_date'),
      ]);
    }

    return $this->t('News items will be assigned automatic unpublish and deletion dates. These dates can be overridden by entering respective dates manually or by re-publishing the content. This article will be deleted on @deleteDate', [
      '@deleteDate' => $this->dateFormatter->format($deleteTime, 'tieto_date'),
    ]);
  }

  /**
   * Return the moderation state for the entity if possible.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return string|null
   *   The state or NULL.
   */
  protected function entityModerationState(FieldableEntityInterface $entity): ?string {
    if (!$entity->hasField('moderation_state')) {
      return NULL;
    }

    return $entity->get('moderation_state')->target_id ?? NULL;
  }

  /**
   * Return the notification message.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The translatable message, or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @todo: Generalize.
   */
  public function notificationMessage(FieldableEntityInterface $entity): ?TranslatableMarkup {
    if ($entity->isNew()) {
      return $this->newEntityNotificationMessage($entity);
    }

    if ($this->isEntityScheduled($entity)) {
      return NULL;
    }

    switch ($this->entityModerationState($entity)) {
      case 'unpublished':
        return $this->draftDeleteNotificationMessage($entity);

      case 'published':
        return $this->unpublishNotificationMessage($entity);

      case 'unpublished_content':
        return $this->archiveNotificationMessage($entity);

      case 'trash':
        return $this->oldDeleteNotificationMessage($entity);

      default:
        return NULL;
    }
  }

  /**
   * Returns the entity unpublish time if possible.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return int|null
   *   The unpublish time or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function entityUnpublishTime(EntityInterface $entity): ?int {
    $offset = $this->getStateOffset($entity, 'unpublished_content');

    if ($offset === NULL) {
      return NULL;
    }

    return $this->offsetLastPublishTime($entity, $offset);
  }

  /**
   * Returns the entity archive time if possible.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return int|null
   *   The archive time or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function entityArchiveTime(EntityInterface $entity): ?int {
    $offset = $this->getStateOffset($entity, 'trash');

    if ($offset === NULL) {
      return NULL;
    }

    return $this->offsetLastPublishTime($entity, $offset);
  }

  /**
   * Return the offset for a state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $state
   *   The state.
   *
   * @return string|null
   *   The date offset or NULL.
   */
  private function getStateOffset(EntityInterface $entity, string $state): ?string {
    $config = $this->lifeCycleConfig->get('fields')[$entity->getEntityTypeId()][$entity->bundle()] ?? [];

    foreach ($config as $setting) {
      if ($setting['target_state'] === $state && !empty($setting['date'])) {
        return $setting['date'];
      }
    }

    return NULL;
  }

  /**
   * Run moderation updates.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function runOperations(): void {
    // @todo: Optimize; maybe set state variables on entity update,
    // iterate through that only. E.g:
    // - tieto_lifecycle_management.operations: id => [timestamp, state].
    foreach ($this->lifeCycleConfig->get('fields') as $entityType => $bundles) {
      /** @var \Drupal\Core\Entity\EntityStorageInterface $entityStorage */
      $entityStorage = $this->entityTypeManager->getStorage($entityType);

      $entityQuery = $entityStorage->getQuery();
      $results = $entityQuery->execute();

      $entityIdsBatched = array_chunk($results, 500, TRUE);

      foreach ($entityIdsBatched as $entityIds) {
        // Note: Entities loaded are the default revision, not latest.
        // @todo: EntityCreatedInterface; see: https://www.drupal.org/node/2833378
        /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\EntityChangedInterface|\Drupal\Core\Entity\FieldableEntityInterface $entity */
        foreach ($entityStorage->loadMultiple($entityIds) as $entity) {
          if (
            $entity->hasField('ignore_lifecycle_management')
            && (bool) $entity->get('ignore_lifecycle_management')->value === TRUE
          ) {
            continue;
          }

          if ($this->isEntityScheduled($entity)) {
            continue;
          }

          $entityId = $entity->id();

          if (
            ($isUnpublished = $this->shouldDeleteUnpublishedEntity($entity))
            || ($isOld = $this->shouldDeleteOldEntity($entity))
          ) {
            $reason = RemovalReason::UNKNOWN;
            if (isset($isUnpublished) && $isUnpublished === TRUE) {
              $reason = RemovalReason::NEVER_PUBLISHED;
            }
            if (isset($isOld) && $isOld === TRUE) {
              $reason = RemovalReason::TOO_OLD;
            }

            // @todo: More data, e.g timestamps?
            $event = new LifeCycleRemoveEvent(
              $entity,
              $reason
            );

            $this->eventDispatcher->dispatch(
              $event::NAME,
              $event
            );

            $info = json_encode([
              'id' => $entityId,
              'title' => $entity->label(),
              'url' => $entity->toUrl('canonical', ['absolute' => TRUE])
                ->toString(TRUE)
                ->getGeneratedUrl(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $entity->delete();
            $this->logger->info("Entity ({$entityId}) has been deleted [reason: {$reason}]. Additional info: {$info}");
            continue;
          }

          $entityConfig = $bundles[$entity->bundle()] ?? [];

          $wasUpdated = FALSE;
          // Update moderation state according to the config, if users didn't
          // add a scheduled update date.
          // No need to re-update, if it already is the target state.
          // @todo: Maybe order this based on offset (DESC), so we don't update
          // multiple times.
          foreach ($entityConfig as $fieldName => $fieldSettings) {
            $fieldSettings['field_name'] = $fieldName;

            // No real reason to update multiple times.
            if ($this->shouldUpdateModerationState($entity, $fieldSettings)) {
              $wasUpdated = TRUE;

              // @todo: More data, e.g timestamps?
              $event = new LifeCycleUpdateEvent(
                $entity,
                $fieldSettings['target_state']
              );

              $this->eventDispatcher->dispatch(
                $event::NAME,
                $event
              );

              $entity->get('moderation_state')
                ->setValue($fieldSettings['target_state']);
              $entity->save();
              $this->logger->info("Entity ({$entityId}) state has been updated to {$fieldSettings['target_state']}.");
              break;
            }
          }

          // @todo?: Maybe check isset($event). If it's not, set the Ignore one.
          // @todo: If ^ is added, dispatch outside the conditional.
          if ($wasUpdated === FALSE) {
            // @todo: More data, e.g timestamps?
            $event = new LifeCycleIgnoreEvent(
              $entity
            );

            $this->eventDispatcher->dispatch(
              $event::NAME,
              $event
            );
          }
        }

        $entityStorage->resetCache($entityIds);
      }
    }

    // @todo: Dispatch LifeCycleEndedEvent (Required, when wanting to send aggregate mails).
  }

  /**
   * Check if an entity is scheduled or not.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE, if it's scheduled.
   */
  public function isEntityScheduled(EntityInterface $entity): bool {
    $entityConfig = $this->lifeCycleConfig->get('fields')[$entity->getEntityTypeId()][$entity->bundle()] ?? [];

    foreach (array_keys($entityConfig) as $scheduleFieldName) {
      /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
      if ($entity->hasField($scheduleFieldName)
        && ($field = $entity->get($scheduleFieldName))
        && !$field->isEmpty()
      ) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Offsets a timestamp.
   *
   * @param int $timestamp
   *   The timestamp.
   * @param string $offset
   *   The offset string, e.g "+1 month".
   *
   * @return int
   *   The timestamp with the offset added.
   */
  public function offsetTimestamp(int $timestamp, string $offset): int {
    return DrupalDateTime::createFromTimestamp($timestamp)
      ->add(DateInterval::createFromDateString($offset))
      ->getTimestamp();
  }

  /**
   * Return the latest published revision, if possible.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return int|null
   *   The last published date or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function entityLastPublishDate(EntityInterface $entity): ?int {
    if ($entity->isNew()) {
      return NULL;
    }

    $keys = $entity->getEntityType()->getKeys();

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $query = $storage->getQuery();
    $query->condition($keys['id'], $entity->id());
    $query->condition($keys['status'], 1);
    $query->condition('moderation_state', 'published');
    $query->sort($keys['revision'], 'desc');
    $query->range(0, 1);
    $query->allRevisions();
    $data = $query->execute();

    if (empty($data)) {
      return NULL;
    }

    /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\EntityChangedInterface $revision */
    $revision = $storage->loadRevision(key($data));

    if ($revision === NULL) {
      return NULL;
    }

    return $revision->getChangedTime();
  }

  /**
   * Returns whether the unpublished entity should be removed or not.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE, if the unpublished entity should be deleted.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function shouldDeleteUnpublishedEntity(EntityInterface $entity): bool {
    $draftDeleteTime = $this->unpublishedEntityDeleteTime($entity);

    if ($draftDeleteTime === NULL) {
      return FALSE;
    }

    return $draftDeleteTime <= $this->time->getRequestTime();
  }

  /**
   * Returns the delete time of a never unpublished entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return int|null
   *   Return the delete time, if possible.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function unpublishedEntityDeleteTime(EntityInterface $entity): ?int {
    $lastPublishDate = $this->entityLastPublishDate($entity);

    // If it has a published date, ignore.
    if ($lastPublishDate !== NULL) {
      return NULL;
    }

    // @todo: Make offset configurable.
    return $this->offsetTimestamp($entity->getChangedTime(), '+1 year');
  }

  /**
   * Returns whether the entity should be removed or not.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE, if the entity should be deleted.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function shouldDeleteOldEntity(EntityInterface $entity): bool {
    $entityDeleteTime = $this->entityDeleteTime($entity);

    if ($entityDeleteTime === NULL) {
      return FALSE;
    }

    return $entityDeleteTime <= $this->time->getRequestTime();
  }

  /**
   * Returns the delete time of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return int|null
   *   Return the delete time, if possible.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function entityDeleteTime(EntityInterface $entity): ?int {
    // @todo: Make offset configurable.
    return $this->offsetLastPublishTime($entity, '+3 years');
  }

  /**
   * Returns whether the entity moderation state should be updated.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param array $fieldSettings
   *   Field settings.
   *
   * @return bool
   *   TRUE, if the moderation state should be updated.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function shouldUpdateModerationState(
    FieldableEntityInterface $entity,
    array $fieldSettings
  ): bool {
    $currentState = $this->entityModerationState($entity);
    if ($currentState === NULL) {
      return FALSE;
    }

    // Invalid settings.
    if (empty($fieldSettings['date']) || empty($fieldSettings['target_state'])) {
      return FALSE;
    }

    $fieldName = $fieldSettings['field_name'];

    // No moderation fields, or scheduling already set by a user.
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    if (!($entity->hasField($fieldName)
      && ($field = $entity->get($fieldName))
      && $field->isEmpty())
    ) {
      return FALSE;
    }

    // Already the target state, or would be an invalid one.
    if (
      $currentState === $fieldSettings['target_state']
      || !$this->isCorrectTransition($currentState, $fieldSettings['target_state'])
    ) {
      return FALSE;
    }

    $moderationUpdateTime = $this->offsetLastPublishTime($entity, $fieldSettings['date']);

    // Was not yet published.
    if ($moderationUpdateTime === NULL) {
      return FALSE;
    }

    return $this->time->getRequestTime() >= $moderationUpdateTime;
  }

  /**
   * Return the last published timestamp with an offset.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $offset
   *   The offset.
   *
   * @return int|null
   *   The last publish date timestamp with the offset applied, or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function offsetLastPublishTime(EntityInterface $entity, string $offset): ?int {
    $lastPublishDate = $this->entityLastPublishDate($entity);

    // Was not yet published.
    if ($lastPublishDate === NULL) {
      return NULL;
    }

    return $this->offsetTimestamp($lastPublishDate, $offset);
  }

}
