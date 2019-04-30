<?php

namespace Drupal\tieto_ldap;

use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function reset;

/**
 * Controller routines for taxonomy importer route.
 */
class TaxonomyImporter extends ImporterBase {

  /**
   * Number of checked LDAP rows for import.
   *
   * @var int
   */
  private $rowsImportChecked = 0;

  /**
   * Array of Term IDs.
   *
   * @var array
   */
  private $tidsImported = [];

  /**
   * Array of Term names from source.
   *
   * @var array
   */
  private $termHierarchy = [
    'unit' => [],
    'location' => [],
    'role' => [],
  ];

  /**
   * Term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface|null
   */
  protected $termStorage;

  /**
   * Returns the term storage.
   *
   * @return \Drupal\taxonomy\TermStorageInterface
   *   The term storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function termStorage(): TermStorageInterface {
    if (!isset($this->termStorage)) {
      $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    }

    return $this->termStorage;
  }

  /**
   * Constructs a page with descriptive content.
   *
   * @param bool $import
   *   TRUE for import, FALSE for test.
   *
   * @return array|null
   *   Import result message or (for CLI only) NULL.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function import(bool $import = FALSE): ?array {
    $mode = ($import ? 'import' : 'test');
    $this->state->set("tieto_ldap.taxonomy_{$mode}_last", $this->time->getRequestTime());
    $this->state->set("tieto_ldap.taxonomy_{$mode}_last_uid", $this->currentUser->id());

    $output = '';
    $attributesStructure = $this->getAttributeStructure();

    $config = $this->configFactory->get('tieto_ldap.taxonomy.settings');
    $ldapServers = $config->get('ldap_server');

    foreach ($ldapServers as $sid) {
      $this->rowsImportChecked = 0;
      if ($sid && ($ldapServer = $this->serverFactory->getServerById($sid))) {
        $ldapServer->connect();
        $ldapServer->bind();

        $ldapServerConfig = $this->configFactory->get('ldap_servers.server.' . $sid);

        $ldapServer->baseDn = $ldapServerConfig->get('base_dn');
        $ldapServer->filter = '(&(objectCategory=person)(objectClass=user)(mail=*)(employeeID=*)(co=*)(!(employeeNumber=*Restructuring and OTI*))(!(extensionAttribute5=*))(!(userAccountControl:1.2.840.113556.1.4.803:=2))(|(employeeType=Employee)(employeeType=Subcontractor)(employeeType=Temporary)))';

        $intermittentAttrs = [[]];
        foreach ($attributesStructure as $vocab => $attributeNames) {
          $intermittentAttrs[] = $attributeNames;
        }

        $attributes = array_merge(...$intermittentAttrs);

        $ldapServer->attributes = $attributes;
        // Should be 1 ?
        $attrsonly = 0;
        $sizelimit = 0;
        $result = $ldapServer->search(
          $ldapServer->baseDn,
          $ldapServer->filter,
          $ldapServer->attributes,
          $attrsonly,
          $sizelimit
        );

        $prefix = '<strong>baseDn:</strong> ' . $ldapServer->baseDn . '</br>';
        $prefix .= '<strong>filter:</strong> ' . $ldapServer->filter . '</br>';
        $info = $this->t('LDAP Query Results at %address:%port: count=%count', [
          '%address' => $ldapServer->get('address'),
          '%port' => $ldapServer->get('port'),
          '%count' => (int) $result['count'],
        ]);

        $resultCount = (int) $result['count'];
        unset($result['count']);
        if (!empty($result)) {
          foreach ($result as $row) {
            unset($row['objectclass']['count']);
            $rowData = [];
            foreach ($attributes as $attribute) {
              $data = $row[$attribute][0];
              $rowData[$attribute] = $data ?: NULL;
            }
            foreach ($attributesStructure as $vocab => $attributeNames) {
              $termHierarchy = [];
              $parent = NULL;
              foreach ($attributeNames as $attributeName) {
                if ($rowData[$attributeName]) {
                  $termHierarchy[$attributeName] = $rowData[$attributeName];
                }
              }
              if (!empty($termHierarchy)) {
                $leaf = &$this->termHierarchy[$vocab];
                $parent = empty($leaf) ? ['children' => []] : $this->termHierarchy[$vocab];
                foreach ($termHierarchy as $attributeName => $label) {
                  if (array_key_exists($label, $parent)) {
                    $leaf[$label]['usercount']++;
                  }
                  else {
                    $leaf[$label]['usercount'] = 1;
                    $leaf[$label]['children'] = [];
                  }
                  $parent = $leaf[$label]['children'];
                  $leaf = &$leaf[$label]['children'];
                }
              }
            }
            $this->rowsImportChecked++;
          }
          if ($import) {
            $this->importTerms();

            // When we checked all source data, we can run inactivateTerms.
            if ($resultCount === $this->rowsImportChecked) {
              $this->inactivateTerms();
            }
            else {
              $message = $this->t('inactivateTerms not invoked: result_count (@result_count) not match rowsImportChecked (@rowsImportChecked)', [
                '@result_count' => $resultCount,
                '@rowsImportChecked' => $this->rowsImportChecked,
              ]);
              $this->messenger()->addWarning($message);
              $this->logger
                ->warning($message);
            }
          }
        }

        if (!$import) {
          $build[$sid]['table']['unit']['#markup'] = $prefix . '<br />' . $info . '<br />';
        }
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
   * Return AD attribute structure based on vocabulary.
   *
   * AD attributes.
   *  o – Organization level 1 (BA)
   *  ou – Organization level 2 (BU)
   *  adminDescription – Organization level 3 (SBU)
   *
   *  co – Location level 1 (country)
   *  l – Location level 2 (city)
   *  physicalDeliveryOfficeName – Location level 3 (office)
   *
   *  TeJobFamily - role name
   *
   * @return array
   *   Attribute structure.
   */
  private function getAttributeStructure(): array {
    return [
      'unit' => ['o', 'ou', 'admindescription'],
      'location' => ['co', 'l', 'physicaldeliveryofficename'],
      'role' => ['tejobfamily'],
    ];
  }

  /**
   * Custom helper function to import taxonomy terms.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function importTerms(): void {
    $termHierarchy = $this->termHierarchy;
    if (!empty($termHierarchy)) {
      foreach ($termHierarchy as $vid => $items) {
        foreach ($items as $name => $data) {
          $this->saveTerms($vid, $name, $data);
        }
      }
    }
  }

  /**
   * Custom helper function to create/update Taxonomy Term().
   *
   * @param string $vid
   *   Vocabulary machine name.
   * @param string $name
   *   Term name.
   * @param array $item
   *   Item data array with usercount and children keys.
   * @param int $parent
   *   Parent term ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function saveTerms(string $vid, string $name, array $item, int $parent = 0): void {
    $userCount = $item['usercount'];

    $query = $this->database
      ->select('taxonomy_term_field_data', 't');
    $query->join('taxonomy_term__parent', 'p', 'p.entity_id = t.tid');
    $query->fields('t', [
      'tid',
      'tieto_ldap_usercount_reference',
    ]);
    $query->condition('t.name', $name);
    $query->condition('t.vid', $vid);
    $query->condition('p.parent_target_id', $parent);
    $result = $query->execute()->fetchAll();
    // @todo: Handle reset() returning false.
    $data = reset($result);

    $tid = $data->tid;
    $tietoLdapUserCount = $data->tieto_ldap_usercount_reference;
    $term = NULL;

    // No Term found, create it.
    if (!$tid) {
      $termData = [
        'name' => $name,
        'vid' => $vid,
        'parent' => $parent,
        'tieto_ldap_usercount' => $userCount,
        'tieto_ldap_usercount_reference' => $userCount,
      ];

      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $this->termStorage()->create($termData);
    }
    elseif ($tietoLdapUserCount !== $userCount) {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $this->termStorage()->load($tid);
    }

    if ($term !== NULL) {
      $actionType = $term->isNew() ? 'term created' : 'term usercount updated';

      $visibleUserCount = $this->ignoreTermUpdate($term) ? 0 : $userCount;
      $term->set('tieto_ldap_usercount', $visibleUserCount);
      $term->set('tieto_ldap_usercount_reference', $userCount);
      $term->save();

      $tid = $term->id();

      $message = $this->t('%vocab: %actionType: %term (tid: @tid, parent: @parent, usercount: @usercount)', [
        '%vocab' => $vid,
        '%term' => $name,
        '@tid' => $tid,
        '@parent' => $parent,
        '@usercount' => $userCount,
        '%actionType' => $actionType,
      ]);

      $this->messenger()->addStatus($message);
      $this->logger->info($message);
    }

    $this->tidsImported[] = $tid;

    if (!empty($item['children'])) {
      foreach ($item['children'] as $childName => $childData) {
        $this->saveTerms($vid, $childName, $childData, $tid);
      }
    }
  }

  /**
   * Inactivate empty (not referenced/not imported) terms.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function inactivateTerms(): void {
    // Get all not imported terms.
    $query = $this->termStorage()->getQuery();
    $query->condition('vid', array_keys($this->getAttributeStructure()), 'IN');
    $query->condition('tid', $this->tidsImported, 'NOT IN');
    $notImportedTids = $query->execute();

    /** @var \Drupal\taxonomy\TermInterface[] $terms */
    $terms = $this->termStorage()->loadMultiple($notImportedTids);
    foreach ($terms as $term) {
      // @todo: Maybe no longer needed here?
      if ($this->ignoreTermUpdate($term) === TRUE) {
        continue;
      }

      $term->set('tieto_ldap_usercount', 0);
      $term->set('tieto_ldap_usercount_reference', 0);
      $term->save();
      $message = $this->t('%vocab: term inactivated: %term (tid:@tid)', [
        '%vocab' => $term->bundle(),
        '%term' => $term->getName(),
        '@tid' => $term->id(),
      ]);
      $this->messenger()->addStatus($message);
      $this->logger->info($message);
    }
  }

  /**
   * Determines whether the term should be updated or not.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term.
   *
   * @return bool
   *   Whether the term should be ignored from LDAP update.
   */
  private function ignoreTermUpdate(TermInterface $term): bool {
    if ($term->hasField('field_ignore_ldap_update') === FALSE) {
      return FALSE;
    }

    return (bool) $term->get('field_ignore_ldap_update')->value === TRUE;
  }

}
