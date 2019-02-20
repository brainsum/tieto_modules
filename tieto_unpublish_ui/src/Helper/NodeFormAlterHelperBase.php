<?php

namespace Drupal\tieto_unpublish_ui\Helper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Base class for FormAlter helpers.
 *
 * @package Drupal\tieto_unpublish_ui\Helper
 */
abstract class NodeFormAlterHelperBase {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The revision manager.
   *
   * @var \Drupal\tieto_unpublish_ui\Service\NodeRevisionManager
   */
  protected $nodeRevisionManager;

  /**
   * The moderation information service.
   *
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * The form array.
   *
   * @var array
   */
  protected $form;

  /**
   * The current node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * NodeFormAlterHelper constructor.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public function __construct(
    array $form,
    FormStateInterface $formState
  ) {
    $this->nodeRevisionManager = \Drupal::service('tieto_unpublish_ui.node_revision_manager');
    $this->dateFormatter = \Drupal::service('date.formatter');
    $this->moderationInfo = \Drupal::service('workbench_moderation.moderation_information');
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $this->time = \Drupal::time();

    $this->form = $form;
    $this->formState = $formState;

    /** @var \Drupal\node\NodeForm $formObject */
    $formObject = $this->formState->getFormObject();
    /** @var \Drupal\node\NodeInterface $node */
    $this->node = $formObject->getEntity();
  }

  /**
   * Get the node from the form state.
   *
   * @return \Drupal\node\NodeInterface
   *   The node.
   */
  public function node(): NodeInterface {
    return $this->node;
  }

  /**
   * Helper for getting the latest and default revision moderation states.
   *
   * @return array
   *   Associative array with "latest" and "states" keys.
   *   The states can be either NULLs or arrays with the
   *   "machine_name", "label", "entity" keys.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getNodeModerationStates(): array {
    $nid = $this->node()->id();
    if (NULL === $nid) {
      return [
        'default' => NULL,
        'latest' => NULL,
      ];
    }
    $entityTypeId = $this->node()->getEntityTypeId();
    $defaultRevisionId = $this->moderationInfo->getDefaultRevisionId($entityTypeId, $nid);
    $latestRevisionId = $this->moderationInfo->getLatestRevisionId($entityTypeId, $nid);

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage($entityTypeId);
    /** @var \Drupal\node\NodeInterface $defaultRevision */
    $defaultRevision = $nodeStorage->loadRevision($defaultRevisionId);
    /** @var \Drupal\node\NodeInterface $latestRevision */
    $latestRevision = $nodeStorage->loadRevision($latestRevisionId);

    $stateNames = $this->getModerationStateLabels();

    $defaultRevisionStates = NULL;
    $latestRevisionStates = NULL;
    if (NULL !== $defaultRevision) {
      $defaultState = $defaultRevision->get('moderation_state')->getString();
      $defaultRevisionStates = [
        'machine_name' => $defaultState,
        'label' => $stateNames[$defaultState],
        'entity' => $defaultRevision,
      ];
    }
    if (NULL !== $latestRevision) {
      $latestState = $latestRevision->get('moderation_state')->getString();
      $latestRevisionStates = [
        'machine_name' => $latestState,
        'label' => $stateNames[$latestState],
        'entity' => $latestRevision,
      ];
    }

    return [
      'default' => $defaultRevisionStates,
      'latest' => $latestRevisionStates,
    ];
  }

  /**
   * Helper function for getting the moderation state labels.
   *
   * @return array
   *   Associative array of state labels keyed by state machine names.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getModerationStateLabels(): array {
    $moderationStates = $this->entityTypeManager->getStorage('moderation_state')->loadMultiple();

    return \array_map(function ($item) {
      /** @var \Drupal\workbench_moderation\Entity\ModerationState $item */
      return $item->label();
    }, $moderationStates);
  }

}
