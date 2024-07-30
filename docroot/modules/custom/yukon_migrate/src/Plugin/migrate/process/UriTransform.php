<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

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
class UriTransform extends ProcessPluginBase {

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

        // SELECT  d.destid1 FROM db.migrate_map_yukon_migrate_basic_page d
        // LEFT JOIN migrate.node m ON d.sourceid1 = m.nid WHERE m.uuid = '38d3c77f-cfd7-4dcf-8bce-c26de6eca988';

        $migrateDB = \Drupal\Core\Database\Database::getConnection('default', 'migrate');
        $migrateQuery = $migrateDB->select('node', 'n');
        $migrateQuery->fields('n', ['nid', 'type']);
        $migrateQuery->condition('n.uuid', $uuid);
        $migrateResult = $migrateQuery->execute()->fetchAssoc();

        $query = \Drupal::database()->select('migrate_map_yukon_migrate_' . $types[$migrateResult['type']], 'm');
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

    return $value;
  }

}
