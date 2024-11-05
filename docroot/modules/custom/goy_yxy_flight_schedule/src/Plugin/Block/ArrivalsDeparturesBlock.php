<?php

namespace Drupal\goy_yxy_flight_schedule\Plugin\Block;

/**
 * @file
 * Contains \Drupal\matrics_data_upload\Plugin\Block\CertificateCheck.
 */

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
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
 *   id = "arrivals_departures_block",
 *   admin_label = @Translation("Arrivals Departures Block"),
 * )
 */
class ArrivalsDeparturesBlock extends BlockBase implements ContainerFactoryPluginInterface {
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
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

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
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, CurrentPathStack $pathStack, RouteMatchInterface $route_match, AliasManagerInterface $path_alias_manager, ClientInterface $http_client, RendererInterface $renderer, ConfigFactoryInterface $config_factory, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->routeMatch = $route_match;
    $this->path = $pathStack;
    $this->pathAliasManager = $path_alias_manager;
    $this->httpClient = $http_client;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self($configuration, $plugin_id, $plugin_definition, $container->get('language_manager'), $container->get('path.current'), $container->get('current_route_match'), $container->get('path_alias.manager'), $container->get('http_client'), $container->get('renderer'), $container->get('config.factory'), $container->get('date.formatter'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $config = $this->configFactory->get('goy_yxy_flight_schedule.settings');
    $districtUrl = $config->get('goy_yxy_flight_schedule_xml_source_path') . '/' . $config->get('goy_yxy_flight_schedule_departures_file_name');
    $response = $this->httpClient->get($districtUrl, ['headers' => ['Accept' => 'text/plain']]);
    $data = (string) $response->getBody();
    $xml = simplexml_load_string($data);

    if ($xml !== FALSE) {
      // Last Updated table caption.
      $last_updated_timestamp = strtotime((string) $xml->date . ' ' . (string) $xml->time);

      $last_updated_date = date('l, F j, Y H:i', $last_updated_timestamp);

      if ($language == 'fr') {
        // For some reason format_date() capitalizes month and day names in
        // French.
        $last_updated_date = $this->dateFormatter->format($last_updated_timestamp, 'custom', 'l j F Y \à H\hi', NULL, 'fr');
      }

      $caption = $this->t('Last Updated:') . ' ' . $last_updated_date;

      // Column headings.
      $header[] = $this->t('Airline');
      $header[] = $this->t('Flight');
      $header[] = $this->t('Cities');

      $header[] = [
        'data' => $this->t('Temperature'),
        'class' => 'temperature',
      ];
      $departure = $this->t('Departure Time');
      $header[] = $departure;
      $header[] = $this->t('Status');

      $departure = $xml->Departure;

      if (count($xml->Departure) > 0) {
        foreach ($departure as $xml_flight) {
          $row = [];

          // Airline name.
          $row[] = (string) $xml_flight->Carrier;

          // Flight number.
          $row[] = (string) $xml_flight->Flight;

          $cities = (string) $xml_flight->Cities;
          $cities = preg_replace('/,(\S)/', ', $1', $cities);
          $row[] = $cities;

          // Temperature at departure destination city.
          $row[] = [
            'data' => intval((string) $xml_flight->TempCel) . '°C',
            'class' => 'temperature',
          ];

          // Arrival/departure time.
          $revised_timestamp = strtotime((string) $xml_flight->SchedDate . ' ' . (string) $xml_flight->RevisedTime);
          $time_separator = ($language == 'fr') ? '\h' : ':';
          $row[] = date('H' . $time_separator . 'i', $revised_timestamp);

          // Flight status.
          $status = (string) $xml_flight->Status;
          if ($status == "On Time") {
            $status_new = $this->t('On Time');
          }
          elseif ($status == "Departed") {
            $status_new = $this->t('Departed');
          }
          elseif ($status == "Delayed") {
            $status_new = $this->t('Delayed');
          }
          elseif ($status == "Arrived") {
            $status_new = $this->t('Arrived');
          }
          elseif ($status == "Cancelled") {
            $status_new = $this->t('Cancelled');
          }

          if (in_array($status, ['Delayed', 'Cancelled'])) {
            $status_class = (string) 'flight-status-problem';
          }
          else {
            $status_class = (string) 'flight-status-ok';
          }
          $row[] = ['data' => $status_new, 'class' => $status_class];
          $rows[] = $row;
        }
      }
    }

    return [
      'yxy' => [
        '#caption' => $caption,
        '#colgroups' => [],
        '#empty' => $this->t('The list of flights is not available at this time.'),
        '#header' => $header,
        '#rows' => $rows,
        '#sticky' => FALSE,
        '#theme' => 'table',
        '#cache' => ['max-age' => 0],
      ],

    ];
  }

}
