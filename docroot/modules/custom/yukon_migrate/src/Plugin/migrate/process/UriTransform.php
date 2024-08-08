<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a yukon_migrate_uri_transform plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: yukon_migrate_uri_transform
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "yukon_migrate_uri_transform"
 * )
 *
 * @DCG
 * ContainerFactoryPluginInterface is optional here. If you have no need for
 * external services just remove it and all other stuff except transform()
 * method.
 */
final class UriTransform extends ProcessPluginBase {

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Database\Database $database
   *   The database.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
  }

  /**
   * Create a new instance of the plugin.
   *
   * @param \Drupal\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, string $plugin_id, mixed $plugin_definition) {
    return new static( //phpcs:ignore
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transformUri($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = str_ireplace('"https://www.yukon.ca', '"', $value);
    $value = str_ireplace('"http://www.yukon.ca', '"', $value);
    $value = str_ireplace('"https://yukon.ca', '"', $value);
    $value = str_ireplace('"http://yukon.ca', '"', $value);

    // [uuid-link:node:ba5dac36-a2a6-48df-a92c-dcba01cb40e5]
    $matches = [];
    while (preg_match('/\[uuid-link:node:([a-z0-9\-]+)\]/i', $value, $matches)) {
      $uuid = $matches[1];

      $types = [
        'blog' => 'blog',
        'campaign_page' => 'campaign_page',
        'campground_directory_record' => 'campground_directory_record',
        'department' => 'department',
        'directory_records_places' => 'places',
        'documents' => 'documents',
        'engagement' => 'engagement',
        'event' => 'event',
        'in_page_alert' => 'in_page_alert',
        'landing_page' => 'landing_page',
        'landing_page_level_2' => 'landing_page_level_2',
        'multi_step_page' => 'multi_step_page',
        'news' => 'news',
        'site_wide_alert' => 'site_wide_alert',
        'topics_page' => 'topics_page',
        'wetkit_page' => 'basic_page',
        'contact' => 'contact',
        'documents_non_branded' => 'documents',
        'homepage' => 'homepage',
        'webform' => 'contact',
      ];

      $typeKeys = array_keys($types);

      // SELECT  d.destid1 FROM db.migrate_map_yukon_migrate_basic_page d
      // LEFT JOIN migrate.node m ON d.sourceid1 = m.nid WHERE
      // m.uuid = '38d3c77f-cfd7-4dcf-8bce-c26de6eca988'.
      $migrateDB = Database::getConnection('default', 'migrate');
      $migrateQuery = $migrateDB->select('node', 'n');
      $migrateQuery->fields('n', ['nid', 'type']);
      $migrateQuery->condition('n.uuid', $uuid);
      $migrateResult = $migrateQuery->execute()->fetchAssoc();

      if ($migrateResult) {
        if ($this->database && method_exists($this->database, 'select')) {
          if (in_array($migrateResult['type'], $typeKeys)) {
            $query = $this->database
              ->select('migrate_map_yukon_migrate_' . $types[$migrateResult['type']], 'm');
            $query->fields('m', ['destid1']);
            $migrateQuery->condition('m.sourceid1', $migrateResult['nid']);
            $nid = $query->execute()->fetchField();

            if ($nid && $nid != 16158) {
              $value = str_ireplace($matches[0], '/node/' . $nid, $value);
            }
            else {
              $value = str_ireplace($matches[0], 'UUID_NOT_FOUND: ' . $uuid, $value);
            }
          }
          else {
            $this->messenger()
              ->addError('Unknown type: ' . $migrateResult['type']);
          }
        }
        else {
          $this->messenger()->addError('Not a database object.');
        }
      }
      else {
        $this->messenger()->addWarning('UUID not found: ' . $uuid);
      }
    }

    return $value;
  }

}
