<?php

namespace Drupal\uiowa_directory_profiles\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\uiowa_directory_profiles\DirectoryProfiles;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for APR routes.
 */
class SitemapController extends ControllerBase {
  use LoggerChannelTrait;

  /**
   * The APR service.
   *
   * @var \Drupal\uiowa_directory_profiles\DirectoryProfiles
   */
  protected $directory_profiles;

  /**
   * The APR settings immutable config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The uiowa_directory_profiles logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The controller constructor.
   *
   * @param \Drupal\uiowa_directory_profiles\DirectoryProfiles $directory_profiles
   *   The uiowa_directory_profiles.directory_profiles service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory service.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client service.
   */
  public function __construct(DirectoryProfiles $directory_profiles, ConfigFactoryInterface $config, ClientInterface $httpClient) {
    $this->directory_profiles = $directory_profiles;
    $this->config = $config->get('uiowa_directory_profiles.settings');
    $this->httpClient = $httpClient;
    $this->logger = $this->getLogger('uiowa_directory_profiles');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('uiowa_directory_profiles.directory_profiles'),
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * Builds the response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function build(Request $request) {
    // The returned sitemap URLs already include a slash so remove ours.
    $path = $this->config->get('directory.path');

    $params = UrlHelper::buildQuery([
      'key' => $this->config->get('api_key'),
      'path' => ltrim($path, '/'),
    ]);

    try {
      $response = $this->httpClient->request('GET', "{$this->directory_profiles->endpoint}/people/sitemap?{$params}", [
        'headers' => [
          'Accept' => 'text/plain',
          'Referer' => $request->getSchemeAndHttpHost(),
        ],
      ]);
    }
    catch (RequestException | GuzzleException $e) {
      // Just throw a 404 here since the Acquia error page is ugly.
      $this->logger->error($e->getMessage());
      throw new NotFoundHttpException();
    }

    $contents = $response->getBody()->getContents();

    return new Response(
      $contents,
      200,
      [
        'Content-Type' => 'text/plain',
      ]
    );
  }

}
