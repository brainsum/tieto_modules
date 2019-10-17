<?php

namespace Drupal\tieto_ldap;

use Drupal\tieto_ldap\Processor\DrupalUserImportProcessor;
use function array_keys;
use function implode;
use function in_array;
use function mb_strtolower;
use function strlen;
use function strpos;
use function user_load_by_name;
use function user_validate_name;

/**
 * Controller routines for user import from LDAP.
 */
class UserImporter extends ImporterBase {

  /**
   * Import users from LDAP.
   *
   * @param bool $import
   *   Import or only display LDAP query result.
   * @param array $ldapServers
   *   LDAP server ID, optional - settings will be overridden if entered.
   * @param string $ldapFilter
   *   LDAP query filter, optional - settings will be overridden if entered.
   *
   * @return array
   *   Render array with result on $import = FALSE;
   *
   * @throws \Exception
   */
  public function import($import = FALSE, array $ldapServers = [], $ldapFilter = ''): array {
    $mode = ($import ? 'import' : 'test');
    $this->state->set('tieto_ldap.user_' . $mode . '_last', $this->time->getRequestTime());
    $this->state->set('tieto_ldap.user_ ' . $mode . '_last_uid', $this->currentUser->id());
    $config = $this->configFactory->get('tieto_ldap.user.settings');

    $output = '';

    if (empty($ldapServers)) {
      $ldapServers = $config->get('ldap_server') ?? [];
    }
    if (empty($ldapFilter)) {
      $ldapFilter = $config->get('ldap_filter') ?? '';
    }

    foreach ($ldapServers as $sid) {
      if ($sid) {
        $ldapServer = $this->serverFactory->getServerById($sid);
        $ldapServer->connect();
        $ldapServer->bind();

        $ldapServerConfig = $this->configFactory->get('ldap_servers.server.' . $sid);

        $ldapServer->baseDn = $ldapServerConfig->get('base_dn');
        $ldapServer->filter = $ldapFilter;

        // Override ldap_user.settings - set user provisioning server to actual.
        $processor = new DrupalUserImportProcessor($sid);

        foreach (['user_attr', 'mail_attr'] as $attribute_name) {
          if ($attribute = $ldapServerConfig->get($attribute_name)) {
            $attributes[$attribute] = [];
          }
        }

        $params = [
          'sid' => $sid,
          'ldap_context' => 'ldap_user_prov_to_drupal',
        ];
        $userAttributes = $processor->alterUserAttributes($attributes, $params);
        $ldapServer->attributes = array_keys($userAttributes);

        $attrsonly = 0;
        $sizelimit = 0;
        $result = $ldapServer->search($ldapServer->baseDn, $ldapServer->filter, $ldapServer->attributes, $attrsonly, $sizelimit);

        $prefix = '<strong>baseDn:</strong> ' . $ldapServer->baseDn . '</br>';
        $prefix .= '<strong>filter:</strong> ' . $ldapServer->filter . '</br>';
        $caption = $this->t('LDAP Query Results at %address:%port: count=%count', [
          '%address' => $ldapServer->get('address'),
          '%port' => $ldapServer->get('port'),
          '%count' => (int) $result['count'],
        ]);

        $searchResultRows = [];
        unset($result['count']);
        if (!empty($result)) {
          foreach ($result as $row) {
            unset($row['objectclass']['count']);
            $rowData = [];
            foreach ($ldapServer->attributes as $attribute) {
              $attribute = mb_strtolower($attribute);
              $data = '-';
              if (isset($row[$attribute])) {
                if (isset($row[$attribute]['count']) && $row[$attribute]['count'] > 1) {
                  $data = implode(',', $row[$attribute]);
                }
                else {
                  $data = $row[$attribute][0];
                }
              }
              $rowData[$attribute] = $data;
            }
            $searchResultRows[] = $rowData;

            if ($import) {
              $ldapUsername = $row[mb_strtolower($ldapServerConfig->get('user_attr'))][0];
              $ldapMail = $row[mb_strtolower($ldapServerConfig->get('mail_attr'))][0];
              $userValues = [
                'name' => $ldapUsername,
                'status' => 1,
              ];
              if ($this->isValidLdapUsername($sid, $ldapUsername) && $this->emailValidator
                ->isValid($ldapMail) && (user_validate_name($ldapUsername) === NULL || $this->emailValidator
                  ->isValid($ldapUsername))) {

                /** @var \Drupal\user\UserInterface $drupalAccount */
                $drupalAccount = \user_load_by_name($userValues['name']);
                // Fix LDAP username changes based on email.
                if (!$drupalAccount) {
                  \Drupal::database()->update('users_field_data')
                    ->fields([
                      'name' => $userValues['name'],
                      'ldap_user_current_dn' => $row['dn'],
                      'ldap_user_puid' => $userValues['name'],
                    ])
                    ->condition('mail', $ldapMail)
                    ->execute();
                  $drupalAccount = \user_load_by_name($userValues['name']);
                }
                // Create user in Drupal.
                if (!$drupalAccount) {
                  $result = $processor->provisionDrupalAccount($userValues);
                  if (!$result) {
                    $this->logger
                      ->warning('%username: user create error from LDAP', ['%username' => $userValues['name']]);
                  }
                  else {
                    $this->logger
                      ->info('%username: user created from LDAP', ['%username' => $userValues['name']]);
                  }
                }
                // Existing user, update ldap_user fields if are empty.
                else {
                  if (!$drupalAccount->get('ldap_user_puid_sid')->value) {
                    $drupalAccount->set('ldap_user_puid', $ldapUsername);
                    $drupalAccount->set('ldap_user_puid_property', $ldapServer->get('unique_persistent_attr'));
                    $drupalAccount->set('ldap_user_puid_sid', $ldapServer->id());
                    $drupalAccount->set('ldap_user_current_dn', $row['dn']);
                    $drupalAccount->set('ldap_user_last_checked', $this->time->getCurrentTime());
                    $drupalAccount->set('ldap_user_ldap_exclude', 0);
                    $drupalAccount->save();
                    $this->logger
                      ->info('%username: ldap_user fields updated from LDAP.', ['%username' => $userValues['name']]);
                  }
                  // Sync blocked user - possibility to re-activate.
                  // Sync if full name is empty.
                  // Sync if location is empty.
                  if (
                    !$drupalAccount->isActive()
                    || ($drupalAccount->hasField('field_user_fullname') && !$drupalAccount->get('field_user_fullname')->value)
                    || ($drupalAccount->hasField('field_location') && !$drupalAccount->get('field_location')->target_id)
                  ) {
                    $processor->drupalUserLogsIn($drupalAccount);
                  }
                }
              }
              else {
                $this->logger
                  ->warning('No valid mail address or valid username (name: %username, mail: %mail) - user not created from LDAP', [
                    '%username' => $ldapUsername,
                    '%mail' => $ldapMail,
                  ]);
              }
            }
          }
        }

        $build[$sid]['table'] = [
          '#theme' => 'table',
          '#prefix' => $prefix,
          '#caption' => $caption,
          '#header' => $ldapServer->attributes,
          '#rows' => $searchResultRows,
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

    return [];
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
      strpos($ldapUsername, '.') !== FALSE
      || (in_array($sid, ['tieto', 'tieto_ap']) && strlen($ldapUsername) > 8)
    );
  }

}
