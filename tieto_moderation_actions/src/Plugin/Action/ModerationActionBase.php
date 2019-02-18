<?php

namespace Drupal\tieto_moderation_actions\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\Plugin\Action\EntityActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_moderation\ModerationInformationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ModerationActionBase.
 *
 * @package Drupal\tieto_moderation_actions\Plugin\Action
 */
abstract class ModerationActionBase extends EntityActionBase {

  /**
   * The moderation info service.
   *
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModerationInformationInterface $mod_info,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->moderationInfo = $mod_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('workbench_moderation.moderation_information'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIf($this->moderationInfo->isModeratableEntity($object))->addCacheableDependency($object);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
