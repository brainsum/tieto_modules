<?php

namespace Drupal\tieto_unpublish_ui\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Class NodeRevisionManager.
 *
 * @package Drupal\tieto_unpublish_ui\Service
 */
class NodeRevisionManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * NodeRevisionManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Return the latest published revision, if possible.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The latest published revision, or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadLatestPublishedRevision(NodeInterface $node) {
    if ($node->isNew()) {
      return NULL;
    }

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $query = $nodeStorage->getQuery();
    $query->condition('nid', $node->id());
    $query->condition('status', 1);
    $query->condition('moderation_state', 'published');
    $query->sort('vid', 'desc');
    $query->range(0, 1);
    $query->allRevisions();
    $data = $query->execute();

    if (empty($data)) {
      return NULL;
    }
    return $nodeStorage->loadRevision(\key($data));
  }

  /**
   * Return the latest unpublished revision, if possible.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The latest published revision, or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadLatestUnpublishedRevision(NodeInterface $node) {
    if ($node->isNew()) {
      return NULL;
    }

    // @todo: Should we check for mod_status?
    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $query = $nodeStorage->getQuery();
    $query->condition('nid', $node->id());
    $query->condition('status', 0);
    $query->condition('moderation_state', 'unpublished_content');
    $query->sort('vid', 'desc');
    $query->range(0, 1);
    $query->allRevisions();
    $data = $query->execute();

    if (empty($data)) {
      return NULL;
    }
    return $nodeStorage->loadRevision(\key($data));
  }

  /**
   * Check if the node has revisions.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return bool
   *   TRUE if there are revisions for the node.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function hasRevisions(NodeInterface $node) {
    if ($node->isNew()) {
      return FALSE;
    }

    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $query = $nodeStorage->getQuery();
    $query->condition('nid', $node->id());
    $query->allRevisions();
    $query->count();

    $revisionCount = (int) $query->execute();
    return $revisionCount >= 2;
  }

}
