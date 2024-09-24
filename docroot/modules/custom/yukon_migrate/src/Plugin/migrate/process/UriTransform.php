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
   * The migration database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $migrateDatabase;

  /**
   * Maps sourceid1 to destid1.
   *
   * @var array
   */
  public static array $mapping;

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

    // Sometimes the $database is a migration object.
    $this->database = Database::getConnection('default', 'default');
    $this->migrateDatabase = Database::getConnection('default', 'migrate');

    if (empty(self::$mapping)) {

      $tables = [
        '0' => 'migrate_map_yukon_migrate_basic_page',
        '1' => 'migrate_map_yukon_migrate_blog',
        '2' => 'migrate_map_yukon_migrate_campground_directory_record',
        '3' => 'migrate_map_yukon_migrate_department_nodes',
        '4' => 'migrate_map_yukon_migrate_places',
        '5' => 'migrate_map_yukon_migrate_documents_page',
        '6' => 'migrate_map_yukon_migrate_engagement',
        '7' => 'migrate_map_yukon_migrate_event',
        '8' => 'migrate_map_yukon_migrate_in_page_alert',
        '9' => 'migrate_map_yukon_migrate_landing_page',
        '10' => 'migrate_map_yukon_migrate_landing_page_level_2',
        '11' => 'migrate_map_yukon_migrate_multi_step_page',
        '12' => 'migrate_map_yukon_migrate_news',
        '13' => 'migrate_map_yukon_migrate_site_wide_alert',
        '14' => 'migrate_map_yukon_migrate_topics_page',
        '15' => 'migrate_map_yukon_migrate_campaign_page',
        '16' => 'migrate_map_yukon_migrate_home_page',
      ];

      $database = Database::getConnection('default', 'default');
      foreach ($tables as $table) {
        $result = $database->select($table, 't')
          ->fields('t', ['sourceid1', 'destid1'])
          ->execute()->fetchAll();

        foreach ($result as $row) {
          self::$mapping[$row->sourceid1] = $row->destid1;
        }
      }
    }

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
    if (empty($value)) {
      return '';
    }

    $value = str_ireplace('"https://www.yukon.ca', '"', $value);
    $value = str_ireplace('"http://www.yukon.ca', '"', $value);
    $value = str_ireplace('"https://yukon.ca', '"', $value);
    $value = str_ireplace('"http://yukon.ca', '"', $value);

    // [uuid-link:node:ba5dac36-a2a6-48df-a92c-dcba01cb40e5]
    $matches = [];
    while (preg_match('/\[uuid-link:node:([^]]*)]/i', $value, $matches)) {
      $uuid = $matches[1];

      $rowNid = $row->get('nid');
      $rowType = $row->get('type');
      $langcode = $row->get('language');

      $migration = $_SERVER['argv'][3] ?? 'unknown';
      if ($migration === '--continue-on-failure') {
        $migration = $_SERVER['argv'][4] ?? 'unknown';
      }
      $message = "Migration: ${migration} UUID: ${uuid} RowNid: ${rowNid} RowType: ${rowType} ";

      if (!$uuid) {
        $message .= 'UUID not found';
        $value = str_ireplace($matches[0], $message, $value);
        $this->messenger()->addError($message);
        continue;
      }

      $sourceNid = $this->findSourceNid($uuid);

      if (!$sourceNid) {
        $message .= 'SourceNid not found';
        $value = str_ireplace($matches[0], "puneet_node/" . $rowNid, $value);
        $this->messenger()->addError($message);
        continue;
      }
      $message .= ' SourceNid: ' . $sourceNid;

      $destNid = $this->findDestNid($sourceNid);
      if ($destNid) {
        $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $destNid);
        $value = str_ireplace($matches[0], '/' . $langcode . $alias, $value);
        if (!empty($mapping[$sourceNid])) {
          $this->messenger()->addWarning($message . ' Duplicate SourceNid found');
        }
        self::$mapping[$sourceNid] = $destNid;
        continue;
      }

      $message .= ' DestNid not found';
      $alias2 = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $sourceNid);
      $value = str_ireplace($matches[0], '/' . $langcode . $alias2, $value);
      $this->messenger()->addError($message);
    }

    return $value;
  }

  /**
   * Get the source nid from the uuid.
   *
   * @param string $uuid
   *   The source uuid.
   *
   * @return mixed|string
   *   The source nid or 0 if not found.
   *
   * @throws \Exception
   */
  protected function findSourceNid(string $uuid) {
    if (empty($uuid)) {
      return '';
    }

    $migrateQuery = $this->migrateDatabase->select('node', 'n');
    $migrateQuery->fields('n', ['nid']);
    $migrateQuery->condition('n.uuid', $uuid);
    return $migrateQuery->execute()->fetchField();
  }

  /**
   * Lookup the destination nid from the source nid.
   *
   * @param string $sourceNid
   *   The source nid.
   *
   * @return int|mixed
   *   The destination nid.
   */
  protected function findDestNid(string $sourceNid) {

    if (!empty($sourceNid) && is_string($sourceNid) && !empty(self::$mapping[$sourceNid])) {
      return self::$mapping[$sourceNid];
    }

    return 0;
  }

}
