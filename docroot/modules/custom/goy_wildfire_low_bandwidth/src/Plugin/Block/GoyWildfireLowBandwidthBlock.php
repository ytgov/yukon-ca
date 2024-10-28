<?php

namespace Drupal\goy_wildfire_low_bandwidth\Plugin\Block;

/**
 * @file
 * Contains \Drupal\matrics_data_upload\Plugin\Block\CertificateCheck.
 */

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\path_alias\AliasManagerInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide Committee I am Part of block.
 *
 * @Block(
 *   id = "goy_wildfire_low_bandwidth_block",
 *   admin_label = @Translation("Goy Wildfire Low Bandwidth Block"),
 * )
 */
class GoyWildfireLowBandwidthBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $path;

  /**
   * Path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $pathAliasManager;

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal\Core\Render\RendererInterface definition.
   *
   * @var Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new BookNavigationBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Path\CurrentPathStack $pathStack
   *   The current path alias.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param Drupal\path_alias\AliasManagerInterface $path_alias_manager
   *   The path alias manager.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client.
   * @param Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, CurrentPathStack $pathStack, RouteMatchInterface $route_match, AliasManagerInterface $path_alias_manager, ClientInterface $http_client, RendererInterface $renderer, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->routeMatch = $route_match;
    $this->path = $pathStack;
    $this->pathAliasManager = $path_alias_manager;
    $this->httpClient = $http_client;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self($configuration, $plugin_id, $plugin_definition, $container->get('language_manager'), $container->get('path.current'), $container->get('current_route_match'), $container->get('path_alias.manager'), $container->get('http_client'), $container->get('renderer'), $container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_path = $this->path->getPath();
    $result = $this->pathAliasManager->getAliasByPath($current_path, 'en');
    $path_new = explode('/', $result);
    if (isset($path_new[3])) {
      $district = str_replace("-fire-district", "", $path_new[3]);
      $district = str_replace("current-wildfires-", "", $district);
      $district = str_replace("-", " ", $district);
    }
    elseif (isset($path_new[2])) {
      $district = str_replace("-fire-district", "", $path_new[2]);
      $district = str_replace("current-wildfires-", "", $district);
      $district = str_replace("-", " ", $district);
    }
    $config = $this->configFactory->get('low_seetings.settings');
    $districtName = rawurlencode("='" . $district . "'");
    $districtUrl = $config->get('goy_wildfire_low_bandwidth_wildfire_status_url') . $config->get('goy_wildfire_low_bandwidth_where_clause') . $districtName . $config->get('goy_wildfire_low_bandwidth_suffix');
    $response = $this->httpClient->get($districtUrl, ['headers' => ['Accept' => 'text/plain']]);
    $data = (string) $response->getBody();
    $wildfire_data = json_decode($data);

    if (!empty($wildfire_data)) {
      $active_out_of_control = [];
      $active_being_held = [];
      $active_under_control = [];
      $extinguished_fires = [];
      $wildfire_info_features = $wildfire_data->features ?? [];
      if ($wildfire_info_features) {
        foreach ($wildfire_info_features as $wildfire_info_feature) {
          $fire_status = substr($wildfire_info_feature->attributes->FIRE_STATUS, 0, 2);
          if ($fire_status === 'OC') {
            $active_out_of_control[] = $wildfire_info_feature->attributes;
          }
          if ($fire_status === 'BH') {
            $active_being_held[] = $wildfire_info_feature->attributes;
          }
          if ($fire_status === 'UC') {
            $active_under_control[] = $wildfire_info_feature->attributes;
          }
          if ($fire_status === 'EX') {
            $extinguished_fires[] = $wildfire_info_feature->attributes;
          }
        }

        $content = [];

        if (count($active_out_of_control) !== 0) {
          $content = array_merge($content, goy_wildfire_low_bandwidth_get_wildfire_details_by_type($active_out_of_control, 'out of control'));
        }
        if (count($active_being_held) !== 0) {
          $content = array_merge($content, goy_wildfire_low_bandwidth_get_wildfire_details_by_type($active_being_held, 'being held'));
        }
        if (count($active_under_control) !== 0) {
          $content = array_merge($content, goy_wildfire_low_bandwidth_get_wildfire_details_by_type($active_under_control, 'under control'));
        }
        if (count($extinguished_fires) !== 0) {
          $content = array_merge($content, goy_wildfire_low_bandwidth_get_wildfire_details_by_type($extinguished_fires, 'extinguished'));
        }

        // Sort by fire name.
        usort($content, function ($a, $b) {
          return strcmp($a['data']['fields']['fire_name']['#markup'], $b['data']['fields']['fire_name']['#markup']);
        });
        $render_service = $this->renderer;
        $rendered = $render_service->renderPlain($content);
      }
      else {
        $rendered = '<p class="empty">' . $this->t('There are currently no reported wildfires in this district.') . '</p>';
      }
    }

    return [
      '#type' => 'markup',
      '#markup' => $rendered,
      '#cache' => ['max-age' => 0],
    ];
  }

}
