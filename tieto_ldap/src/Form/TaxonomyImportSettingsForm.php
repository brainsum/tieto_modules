<?php

namespace Drupal\tieto_ldap\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\ldap_servers\ServerFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tieto_ldap\TaxonomyImporter;

/**
 * Defines a form that configures taxonomy import settings.
 */
class TaxonomyImportSettingsForm extends ConfigFormBase {

  /**
   * LDAP server factory.
   *
   * @var \Drupal\ldap_servers\ServerFactory
   */
  protected $ldapServerFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ldap.servers')
    );
  }

  /**
   * LdapQueryForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\ldap_servers\ServerFactory $ldapServerFactory
   *   LDAP server factory.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ServerFactory $ldapServerFactory
  ) {
    parent::__construct($configFactory);

    $this->ldapServerFactory = $ldapServerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tieto_ldap_taxonomy_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'tieto_ldap.taxonomy.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('tieto_ldap.taxonomy.settings');

    $servers = $this->ldapServerFactory->getAllServers();
    if (empty($servers)) {
      $this->messenger()->addStatus($this->t('No ldap servers configured.  Please configure a server before an ldap query.'), 'error');
    }
    else {
      foreach ($servers as $sid => $ldap_server) {
        $ldap_servers[$sid] = $ldap_server->get('label') . ' (' . $ldap_server->get('address') . ')';
      }
    }

    $form['result'] = $form_state->get('result');
    $form['#tree'] = TRUE;
    $form['ldap_server'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('LDAP Server'),
      '#options' => $ldap_servers,
      '#multiple' => FALSE,
      '#default_value' => $config->get('ldap_server') ?: '',
      '#description' => '',
    ];

    $form['test'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration and TEST taxonomy import'),
      '#submit' => ['::submitForm', '::submitTest'],
    ];

    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration and IMPORT taxonomies'),
      '#submit' => ['::submitForm', '::submitImport'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('tieto_ldap.taxonomy.settings')
      ->set('ldap_server', $values['ldap_server'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Test import users.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitTest(array &$form, FormStateInterface $formState): void {
    $this->messenger()->addStatus('Test triggered.');
    $importer = new TaxonomyImporter();
    $result = $importer->import();
    $formState->set('result', $result);
    $formState->setRebuild();
  }

  /**
   * Import users.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitImport(): void {
    $this->messenger()->addStatus('Import triggered.');
    $importer = new TaxonomyImporter();
    $importer->import(TRUE);
  }

}
