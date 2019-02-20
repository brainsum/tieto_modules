<?php

namespace Drupal\tieto_moderation_actions\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workbench_moderation\ModerationStateInterface;

/**
 * Class StateManager.
 *
 * @package Drupal\tieto_moderation_actions\Service
 */
final class ActionManager {

  use StringTranslationTrait;

  public const STATE_PLUGIN_NAME = 'change_wm_state';

  /**
   * Storage for 'Action' items.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $actionStorage;

  /**
   * StateManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->actionStorage = $entityTypeManager->getStorage('action');
  }

  /**
   * Create an action for a state, if it doesn't already exist.
   *
   * @param \Drupal\workbench_moderation\ModerationStateInterface $state
   *   The moderation state.
   */
  public function createActionFromState(ModerationStateInterface $state) {
    $moderationActionId = static::STATE_PLUGIN_NAME . '.' . $state->id();

    if ($this->actionStorage->load($moderationActionId) === NULL) {
      /** @var \Drupal\Core\Action\ActionInterface $action */
      $action = $this->actionStorage->create([
        'id' => $moderationActionId,
        'type' => 'node',
        'label' => $this->t('Set to @label', ['@label' => $state->label()]),
        'configuration' => [
          'wm_state' => $state->id(),
        ],
        'plugin' => static::STATE_PLUGIN_NAME,
      ]);
      $action->trustData()->save();
    }
  }

  /**
   * Delete an action for a state.
   *
   * @param \Drupal\workbench_moderation\ModerationStateInterface $state
   *   Moderation state.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteActionForState(ModerationStateInterface $state) {
    $moderationActionId = static::STATE_PLUGIN_NAME . '.' . $state->id();

    if ($action = $this->actionStorage->load($moderationActionId)) {
      $action->delete();
    }
  }

}
