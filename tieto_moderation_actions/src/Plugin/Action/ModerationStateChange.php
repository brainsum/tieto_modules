<?php

namespace Drupal\tieto_moderation_actions\Plugin\Action;

use function array_map;
use Drupal;
use Drupal\core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_moderation\ModerationInformationInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Changes the entity's Workbench Moderation state to the selected one.
 *
 * @Action(
 *   id = "change_wm_state",
 *   label = @Translation("Set Workbench Moderation State for selected content"),
 *   type = "node"
 * )
 */
final class ModerationStateChange extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * Moderation info.
   *
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * ModerationStateChange constructor.
   *
   * @param array $configuration
   *   Plugin config.
   * @param string $pluginId
   *   Plugin ID.
   * @param mixed $pluginDefinition
   *   Plugin definition.
   * @param \Drupal\workbench_moderation\ModerationInformationInterface $moderationInfo
   *   Moderation info.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    ModerationInformationInterface $moderationInfo
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->moderationInfo = $moderationInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration, $pluginId, $pluginDefinition,
      $container->get('workbench_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'wm_state' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $wmStates = [];

    try {
      $wmStates = $this->moderationStateNames();
    }
    catch (Exception $exception) {
      $this->messenger()->addError($this->t('Error while loading moderation states: %exception', [
        '%exception' => $exception->getMessage(),
      ]));
    }

    $form['wm_state'] = [
      '#type' => 'radios',
      '#title' => $this->t('Workbench Moderation State'),
      '#options' => $wmStates,
      '#default_value' => $this->configuration['wm_state'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['wm_state'] = $form_state->getValue('wm_state');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(EntityInterface $entity = NULL) {
    if ($entity !== NULL && $this->moderationInfo->isModeratableEntity($entity)) {
      $entity->moderation_state->target_id = $this->configuration['wm_state'];
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $returnAsObject = FALSE) {
    /** @var \Drupal\Core\Access\AccessResult $access */
    $access = $object->access('update', $account, TRUE)
      ->andIf(AccessResult::allowedIf($this->moderationInfo->isModeratableEntity($object)))
      ->addCacheableDependency($object);

    return ($returnAsObject ? $access : $access->isAllowed());
  }

  /**
   * Get state names.
   *
   * @return array
   *   State names.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function moderationStateNames(): array {
    $moderationStates = Drupal::entityTypeManager()
      ->getStorage('moderation_state')
      ->loadMultiple();

    return array_map(function ($item) {
      /** @var \Drupal\workbench_moderation\ModerationStateInterface $item */
      return $item->label();
    }, $moderationStates);
  }

}
