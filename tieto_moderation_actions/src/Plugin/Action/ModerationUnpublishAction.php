<?php

namespace Drupal\tieto_moderation_actions\Plugin\Action;

/**
 * Unpublishes an entity.
 *
 * @Action(
 *   id = "moderation_unpublish_action",
 *   label = @Translation("Unpublish selected pages"),
 *   type = "node"
 * )
 */
class ModerationUnpublishAction extends ModerationActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity
      ->set('moderation_state', 'unpublished_content')
      ->save();
  }

}
