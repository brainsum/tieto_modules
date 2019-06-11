<?php

namespace Drupal\tieto_ldap\Processor;

use Drupal;
use Drupal\ldap_user\Processor\DrupalUserProcessor;

/**
 * Handles processing of a user from LDAP to Drupal.
 */
class DrupalUserImportProcessor extends DrupalUserProcessor {

  /**
   * Constructor.
   *
   * @param string $sid
   *   Server id.
   */
  public function __construct($sid) {
    parent::__construct();
    $config = Drupal::service('config.factory')->getEditable('ldap_user.settings');
    $config->set('drupalAcctProvisionServer', $sid);
    $this->config = $config;
  }

}
