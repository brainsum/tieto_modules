<?php

namespace Drupal\tieto_moderation_actions\Plugin\Action;

/**
 * Archives an entity.
 *
 * @Action(
 *   id = "moderation_archive_action",
 *   label = @Translation("Archive selected pages"),
 *   type = "node"
 * )
 */
class ModerationArchiveAction extends ModerationActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity
      ->set('moderation_state', 'trash')
      ->save();
  }

}
