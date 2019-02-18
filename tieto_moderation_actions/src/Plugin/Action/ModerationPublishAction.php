<?php

namespace Drupal\tieto_moderation_actions\Plugin\Action;

/**
 * Publishes an entity.
 *
 * @Action(
 *   id = "moderation_publish_action",
 *   label = @Translation("Publish selected pages"),
 *   type = "node"
 * )
 */
class ModerationPublishAction extends ModerationActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity
      ->set('moderation_state', 'published')
      ->save();
  }

}
