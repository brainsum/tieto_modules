<?php

namespace Drupal\tieto_ldap;

use Drupal\tieto_ldap\Processor\DrupalUserImportProcessor;

/**
 * Controller routines for user import from LDAP.
 */
class UserImporter extends ImporterBase {

  /**
   * Import users from LDAP.
   *
   * @param bool $import
   *   Import or only display LDAP query result.
   * @param array $ldap_servers
   *   LDAP server ID, optional - settings will be overridden if entered.
   * @param string $ldap_filter
   *   LDAP query filter, optional - settings will be overridden if entered.
   *
   * @return array
   *   Render array with result on $import = FALSE;
   *
   * @throws \Exception
   */
  public function import($import = FALSE, array $ldap_servers = [], $ldap_filter = '') {
    $mode = ($import ? 'import' : 'test');
    $this->state->set('tieto_ldap.user_' . $mode . '_last', $this->time->getRequestTime());
    $this->state->set('tieto_ldap.user_ ' . $mode . '_last_uid', $this->currentUser->id());
    $config = $this->configFactory->get('tieto_ldap.user.settings');

    $output = '';

    if (empty($ldap_servers)) {
      $ldap_servers = $config->get('ldap_server');
    }
    if (empty($ldap_filter)) {
      $ldap_filter = $config->get('ldap_filter');
    }

    foreach ($ldap_servers as $sid) {
      if ($sid) {
        $ldap_server = $this->serverFactory->getServerById($sid);
        $ldap_server->connect();
        $ldap_server->bind();

        $ldap_server_config = $this->configFactory->get('ldap_servers.server.' . $sid);

        $ldap_server->baseDn = $ldap_server_config->get('base_dn');
        $ldap_server->filter = $ldap_filter;

        // Override ldap_user.settings - set user provisioning server to actual.
        $processor = new DrupalUserImportProcessor($sid);

        foreach (['user_attr', 'mail_attr'] as $attribute_name) {
          if ($attribute = $ldap_server_config->get($attribute_name)) {
            $attributes[$attribute] = [];
          }
        }

        $params = [
          'sid' => $sid,
          'ldap_context' => 'ldap_user_prov_to_drupal',
        ];
        $attributes_required_by_user_module_mappings = $processor->alterUserAttributes($attributes, $params);
        $ldap_server->attributes = \array_keys($attributes_required_by_user_module_mappings);

        $attrsonly = 0;
        $sizelimit = 0;
        $result = $ldap_server->search($ldap_server->baseDn, $ldap_server->filter, $ldap_server->attributes, $attrsonly, $sizelimit);

        $prefix = '<strong>baseDn:</strong> ' . $ldap_server->baseDn . '</br>';
        $prefix .= '<strong>filter:</strong> ' . $ldap_server->filter . '</br>';
        $caption = t('LDAP Query Results at %address:%port: count=%count', [
          '%address' => $ldap_server->get('address'),
          '%port' => $ldap_server->get('port'),
          '%count' => (int) $result['count'],
        ]);

        $search_result_rows = [];
        unset($result['count']);
        if (!empty($result)) {
          foreach ($result as $row) {
            unset($row['objectclass']['count']);
            $row_data = [];
            foreach ($ldap_server->attributes as $attribute) {
              $attribute = \mb_strtolower($attribute);
              $data = '-';
              if (isset($row[$attribute])) {
                if (isset($row[$attribute]['count']) && $row[$attribute]['count'] > 1) {
                  $data = \implode(',', $row[$attribute]);
                }
                else {
                  $data = $row[$attribute][0];
                }
              }
              $row_data[$attribute] = $data;
            }
            $search_result_rows[] = $row_data;

            if ($import) {
              $ldapUsername = $row[\mb_strtolower($ldap_server_config->get('user_attr'))][0];
              $ldap_mail = $row[\mb_strtolower($ldap_server_config->get('mail_attr'))][0];
              $user_values = [
                'name' => $ldapUsername,
                'status' => 1,
              ];
              if ($this->isValidLdapUsername($sid, $ldapUsername) && $this->emailValidator
                ->isValid($ldap_mail) && (\user_validate_name($ldapUsername) === NULL || $this->emailValidator
                  ->isValid($ldapUsername))) {
                $drupal_account = \user_load_by_name($user_values['name']);
                // Create user in Drupal.
                if (!$drupal_account) {
                  $result = $processor->provisionDrupalAccount($user_values);
                  if (!$result) {
                    $this->logger
                      ->warning('%username: user create error from LDAP', ['%username' => $user_values['name']]);
                  }
                  else {
                    $this->logger
                      ->info('%username: user created from LDAP', ['%username' => $user_values['name']]);
                  }
                }
                // Existing user, update ldap_user fields if are empty.
                else {
                  if (!$drupal_account->get('ldap_user_puid_sid')->value) {
                    $drupal_account->set('ldap_user_puid', $ldapUsername);
                    $drupal_account->set('ldap_user_puid_property', $ldap_server->get('unique_persistent_attr'));
                    $drupal_account->set('ldap_user_puid_sid', $ldap_server->id());
                    $drupal_account->set('ldap_user_current_dn', $row['dn']);
                    $drupal_account->set('ldap_user_last_checked', $this->time->getCurrentTime());
                    $drupal_account->set('ldap_user_ldap_exclude', 0);
                    $drupal_account->save();
                    $this->logger
                      ->info('%username: ldap_user fields updated from LDAP.', ['%username' => $user_values['name']]);
                  }
                  // Sync blocked user - possibility to re-activate.
                  // Sync if full name is empty.
                  // Sync if location is empty.
                  if (
                    !$drupal_account->isActive()
                    || !$drupal_account->get('field_user_fullname')->value
                    || !$drupal_account->get('field_location')->target_id
                  ) {
                    $processor->drupalUserLogsIn($drupal_account);
                  }
                }
              }
              else {
                $this->logger
                  ->warning('No valid mail address or valid username (name: %username, mail: %mail) - user not created from LDAP', [
                    '%username' => $ldapUsername,
                    '%mail' => $ldap_mail,
                  ]);
              }
            }
          }
        }

        $build[$sid]['table'] = [
          '#theme' => 'table',
          '#prefix' => $prefix,
          '#caption' => $caption,
          '#header' => $ldap_server->attributes,
          '#rows' => $search_result_rows,
        ];
      }
    }

    if (PHP_SAPI !== 'cli') {
      $output .= $this->renderer->render($build);
      return [
        '#type' => 'markup',
        '#markup' => $output,
      ];
    }
  }

  /**
   * Validate LDAP username for Tieto LDAP servers.
   *
   * The username should be <= 8 char length without contains '.' char.
   *
   * @param string $sid
   *   LDAP server id.
   * @param string $ldapUsername
   *   Username form LDAP.
   *
   * @return bool
   *   TRUE | FALSE
   */
  public function isValidLdapUsername($sid, $ldapUsername): bool {
    return !(
      \strpos($ldapUsername, '.') !== FALSE
      || (\in_array($sid, ['tieto', 'tieto_ap']) && \strlen($ldapUsername) > 8)
    );
  }

}
