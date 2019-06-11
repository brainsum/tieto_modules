<?php

namespace Drupal\node_revision_status\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use function key;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Display the latest revision status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_latest_revision_status")
 */
class NodeLatestRevisionStatus extends FieldPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function init(
    ViewExecutable $view,
    DisplayPluginBase $display,
    array &$options = NULL
  ) {
    parent::init($view, $display, $options);

    $this->additional_fields['nid'] = 'nid';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $this->getEntity($values);
    $entityType = $entity->getEntityTypeId();
    $entityId = $entity->id();
    /** @var \Drupal\node\NodeStorageInterface $entityStorage */
    $entityStorage = $this->entityTypeManager
      ->getStorage($entityType);

    $currentRevisionId = $entityStorage->getQuery()
      ->condition('nid', $entityId)
      ->allRevisions()
      ->sort('vid', 'DESC')
      ->range(0, 1)
      ->execute();
    $currentRevisionId = key($currentRevisionId);

    /** @var \Drupal\node\NodeInterface $currentRevision */
    $currentRevision = $entityStorage
      ->loadRevision($currentRevisionId);

    return [
      '#markup' => $currentRevision->isPublished() ? '' : $this->t('Yes'),
    ];
  }

}
