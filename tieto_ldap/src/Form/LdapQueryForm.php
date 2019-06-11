<?php

namespace Drupal\tieto_ldap\Form;

use function array_map;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\ldap_servers\ServerFactory;
use function explode;
use function implode;
use function mb_strtolower;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use function trim;

/**
 * Defines a form that run ldap query with specified filter.
 */
class LdapQueryForm extends FormBase {

  /**
   * LDAP server factory.
   *
   * @var \Drupal\ldap_servers\ServerFactory
   */
  protected $ldapServerFactory;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ldap.servers'),
      $container->get('renderer')
    );
  }

  /**
   * LdapQueryForm constructor.
   *
   * @param \Drupal\ldap_servers\ServerFactory $ldapServerFactory
   *   LDAP server factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   */
  public function __construct(
    ServerFactory $ldapServerFactory,
    RendererInterface $renderer
  ) {
    $this->ldapServerFactory = $ldapServerFactory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tieto_ldap_query_form';
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
      foreach ($servers as $sid => $ldapServer) {
        $ldapServers[$sid] = $ldapServer->get('label') . ' (' . $ldapServer->get('address') . ')';
      }
    }

    $form['#tree'] = TRUE;
    $form['ldap_server'] = [
      '#type' => 'radios',
      '#title' => $this->t('LDAP Server'),
      '#options' => $ldapServers,
      '#required' => TRUE,
      '#default_value' => $config->get('ldap_server') ?: '',
    ];

    $form['ldap_query_filter'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Filter'),
      '#default_value' => '(&(objectCategory=person)(objectClass=user)(mail=*)(employeeID=*)(co=*)(!(employeeNumber=*Restructuring and OTI*))(!(extensionAttribute5=*))(!(userAccountControl:1.2.840.113556.1.4.803:=2))(|(employeeType=Employee)(employeeType=Subcontractor)(employeeType=Temporary)))',
      '#required' => TRUE,
    ];

    $form['ldap_query_attributes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Attributes'),
      '#default_value' => implode("\n", ['uid', 'mail']),
      '#required' => TRUE,
      '#description' => $this->t('One per line.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run LDAP query'),
      '#submit' => ['::submitForm'],
    ];

    $form['result'] = $form_state->get('result');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sid = $form_state->getValue('ldap_server');

    $ldapServer = $this->ldapServerFactory->getServerById($sid);
    $ldapServer->connect();
    $ldapServer->bind();

    $ldapServerConfig = $this->configFactory()->get("ldap_servers.server.$sid");

    $ldapServer->baseDn = $ldapServerConfig->get('basedn');
    $ldapServer->filter = trim($form_state->getValue('ldap_query_filter'));
    $attributes = explode("\n", trim($form_state->getValue('ldap_query_attributes')));
    $attributes = array_map('trim', $attributes);
    $ldapServer->attributes = $attributes;
    $attrsonly = 0;
    $sizelimit = 0;
    $result = $ldapServer->search($ldapServer->baseDn, $ldapServer->filter, $ldapServer->attributes, $attrsonly, $sizelimit);

    $output = '';
    $filter = $ldapServer->filter;
    if ($ldapServerConfig->get('search_filter')) {
      $filter = '&(' . $ldapServerConfig->get('search_filter') . $filter . ')';
    }
    $prefix = '<strong>baseDn:</strong> ' . $ldapServer->baseDn . '</br>';
    $prefix .= '<strong>filter:</strong> ' . $filter . '</br>';
    $caption = $this->t('LDAP Query Results at %address:%port: count=%count', [
      '%address' => $ldapServer->get('address'),
      '%port' => $ldapServer->get('port'),
      '%count' => $result['count'],
    ]);

    $searchResultRows = [];
    unset($result['count']);
    if (!empty($result)) {
      foreach ($result as $row) {
        unset($row['objectclass']['count']);
        $rowData = [];
        foreach ($attributes as $attribute) {
          $attribute = mb_strtolower($attribute);
          $data = '-';
          if (isset($row[$attribute])) {
            if ($row[$attribute]['count'] > 1) {
              $data = implode(',', $row[$attribute]);
            }
            else {
              $data = $row[$attribute][0];
            }
          }
          $rowData[$attribute] = $data;
        }
        $searchResultRows[] = $rowData;
      }
    }

    $build[$sid]['table'] = [
      '#theme' => 'table',
      '#prefix' => $prefix,
      '#caption' => $caption,
      '#header' => $attributes,
      '#rows' => $searchResultRows,
    ];

    if (PHP_SAPI !== 'cli') {
      $output .= $this->renderer->render($build);
      $form_state->set('result', [
        '#type' => 'markup',
        '#markup' => '<div>' . $output . '</div>',
      ]);
      $form_state->setRebuild();
    }
  }

}
