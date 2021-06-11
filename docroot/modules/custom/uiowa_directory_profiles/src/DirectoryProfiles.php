<?php

namespace Drupal\uiowa_directory_profiles;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * The APR service sets some dynamic properties based on the environment.
 *
 * @property string $environment The APR environment.
 * @property string $endpoint The APR API endpoint.
 */
class DirectoryProfiles {
  /**
   * The APR settings config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * The uiowa_directory_profiles logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The APR environment.
   *
   * @var string
   */
  protected $environment;

  /**
   * The APR API endpoint.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * Constructs an DirectoryProfiles service object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The uiowa_directory_profiles logger channel.
   *
   * @throws \Exception
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->config = $config_factory->get('uiowa_directory_profiles.settings');
    $this->logger = $logger;
    $this->environment = $this->setEnvironment();
    $this->endpoint = $this->setEndpoint();
  }

  /**
   * Get a protected property.
   *
   * @param string $name
   *   The property name.
   *
   * @return mixed
   *   The property value.
   */
  public function __get($name) {
    return $this->$name;
  }

  /**
   * Set the environment value based on config first then AH environment.
   */
  protected function setEnvironment() {
    if ($env = $this->config->get('environment')) {
      $this->logger->info('APR environment configuration set to @env. Using config instead of AH environment.', [
        '@env' => $env,
      ]);

      $environment = $env;
    }
    else {
      $env = getenv('AH_SITE_ENVIRONMENT');

      switch ($env) {
        case 'test':
        case 'prod':
          $environment = 'prod';
          break;

        default:
          $environment = 'test';
          break;
      }
    }

    return $environment;
  }

  /**
   * Set the API endpoint based on the environment property.
   *
   * @throws \Exception
   *
   * @return string
   *   The API endpoint.
   */
  protected function setEndpoint() {
    $endpoint = '';

    if ($this->environment == 'test') {
      $endpoint = 'https://profiles-test.uiowa.edu';
    }
    elseif ($this->environment == 'prod') {
      $endpoint = 'https://profiles.uiowa.edu';
    }

    if (!isset($endpoint)) {
      $this->logger->error('Invalid environment @env. Unable to set API endpoint.', [
        '@env' => $this->environment,
      ]);
    }

    return $endpoint;
  }

}
