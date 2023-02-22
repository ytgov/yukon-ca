<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Component\Utility\UrlHelper;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateStubInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Provides a yukon_migrate_links plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: yukon_migrate_links
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "yukon_migrate_links"
 * )
 *
 * @DCG
 * ContainerFactoryPluginInterface is optional here. If you have no need for
 * external services just remove it and all other stuff except transform()
 * method.
 */
class Links extends MigrationLookup {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrateLookupInterface $migrate_lookup, MigrateStubInterface $migrate_stub) {
    $configuration['migration'] = [
      'yukon_migrate_basic_page',
      'yukon_migrate_basic_page_translations',
    ];
    $configuration['no_stub'] = TRUE;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $migrate_lookup, $migrate_stub);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $attributes = unserialize($value['attributes'], ['allowed_classes' => FALSE]);
    // Drupal 6/7 link attributes might be double serialized.
    if (!is_array($attributes)) {
      $attributes = unserialize($attributes, ['allowed_classes' => FALSE]);
    }

    // In rare cases Drupal 6/7 link attributes are triple serialized. To avoid
    // further problems with them we set them to an empty array in this case.
    if (!is_array($attributes)) {
      $attributes = [];
    }

    // Massage the values into the correct form for the link.
    $uri = $value['url'];
    if (!UrlHelper::isExternal($uri) && preg_match('/node\/(\d+)/', $uri, $matches)) {
      $source_nid = $matches[1];

      $destination_id = parent::transform($source_nid, $migrate_executable, $row, $destination_property);
      if (!empty($destination_id)) {
        $uri = "internal:/node/{$destination_id}";
      }
    }

    $route['uri'] = $uri;
    $route['options']['attributes'] = $attributes;
    $route['title'] = $value['title'];
    return $route;
  }

}
