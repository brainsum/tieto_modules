<?php

namespace Drupal\tieto_tibr\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TibrHostResolver.
 *
 * @package Drupal\tieto_tibr\Service
 */
class TibrHostResolver {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * TibrHostResolver constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(RequestStack $requestStack, ConfigFactoryInterface $configFactory) {
    $this->request = $requestStack->getCurrentRequest();
    $this->config = $configFactory->get('tieto_tibr.settings');
  }

  /**
   * Helper function to determine tibr host from the http host.
   *
   * @return string
   *   The tibr host.
   */
  public function resolve() {
    $conditionalHosts = $this->config->get('alternative_hosts');
    if (empty($conditionalHosts) || !is_array($conditionalHosts)) {
      return $this->config->get('default_host');
    }

    $httpHost = $this->request->getHttpHost();
    if (array_key_exists($httpHost, $conditionalHosts)) {
      return $conditionalHosts[$httpHost];
    }

    return $this->config->get('default_host');
  }

  /**
   * Returns the module config.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The module config.
   */
  public function getConfig() {
    return $this->config;
  }

}
