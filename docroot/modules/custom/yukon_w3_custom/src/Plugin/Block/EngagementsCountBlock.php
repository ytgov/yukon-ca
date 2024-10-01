<?php

namespace Drupal\yukon_w3_custom\Plugin\Block;

/**
 * @file
 * Contains \Drupal\matrics_data_upload\Plugin\Block\CertificateCheck.
 */

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide Committee I am Part of block.
 *
 * @Block(
 *   id = "engagements_count_block",
 *   admin_label = @Translation("Engagements Count Block"),
 * )
 */
class EngagementsCountBlock extends BlockBase implements ContainerFactoryPluginInterface {
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
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, RouteMatchInterface $route_match, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->routeMatch = $route_match;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self($configuration, $plugin_id, $plugin_definition, $container->get('language_manager'), $container->get('current_route_match'), $container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'block_content' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['block_content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content'),
      '#default_value' => $this->configuration['block_content']['value'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['block_content'] = $values['block_content'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $conn = $this->connection;
    $data = $conn->select('node__field_engagement_status', 'a')
      ->fields('a', ['field_engagement_status_value']);
    $data->join('node_field_data', 'n', 'a.entity_id = n.nid');
    $result = $data->condition('a.bundle', 'engagement', '=')
      ->condition('n.langcode', 'en', '=')
      ->condition('n.status', '1', '=')
      ->execute()
      ->fetchAll();

    $open = 0;
    $closed_with_results = 0;
    $closed = 0;
    $open_with_results = 0;
    $total_eng = count($result);
    foreach ($result as $value) {
      if ($value->field_engagement_status_value == "Open") {
        $open = $open + 1;
      }
      if ($value->field_engagement_status_value == "Closed with results") {
        $closed_with_results = $closed_with_results + 1;
      }
      if ($value->field_engagement_status_value == "Closed") {
        $closed = $closed + 1;
      }
      if ($value->field_engagement_status_value == "Open with results") {
        $open_with_results = $open_with_results + 1;
      }
    }
    $output['open'] = $open;
    $output['open_with_results'] = $open_with_results;
    $output['closed'] = $closed;
    $output['closed_with_results'] = $closed_with_results;
    $output['total_eng'] = $total_eng;
    $output['left_data'] = $this->configuration['block_content']['value'];

    return [
      '#theme' => 'engagements_count',
      '#item' => $output,
      '#cache' => ['max-age' => 0],
    ];
  }

}
