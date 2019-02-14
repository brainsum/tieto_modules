<?php

namespace Drupal\tieto_ldap;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ImporterBase.
 *
 * @package Drupal\tieto_ldap
 */
abstract class ImporterBase {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * LDAP Server factory.
   *
   * @var \Drupal\ldap_servers\ServerFactory
   */
  protected $serverFactory;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ImporterBase constructor.
   */
  public function __construct() {
    $this->state = \Drupal::state();
    $this->time = \Drupal::time();
    $this->configFactory = \Drupal::configFactory();
    $this->serverFactory = \Drupal::service('ldap.servers');
    $this->currentUser = \Drupal::currentUser();
    $this->emailValidator = \Drupal::service('email.validator');
    $this->logger = \Drupal::logger('tieto_ldap');
    $this->renderer = \Drupal::service('renderer');
    $this->database = \Drupal::database();
    $this->entityTypeManager = \Drupal::entityTypeManager();
  }

}
