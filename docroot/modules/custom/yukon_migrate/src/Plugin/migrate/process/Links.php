<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateStubInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class Links extends MigrationLookup implements ContainerFactoryPluginInterface {
  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs a MigrationLookup object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The Migration the plugin is being used in.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrated lookup service.
   * @param \Drupal\migrate\MigrateStubInterface $migrate_stub
   *   The migrated stub service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $migration_plugin_manager
   *   The Migration Plugin Manager Interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrateLookupInterface $migrate_lookup, MigrateStubInterface $migrate_stub, MigrationPluginManager $migration_plugin_manager) {
    $this->migrationPluginManager = $migration_plugin_manager;
    foreach ($this->migrationPluginManager->createInstancesByTag('node') as $migration) {
      $configuration['migration'][] = $migration->id();
    }
    $configuration['no_stub'] = TRUE;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $migrate_lookup, $migrate_stub);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('migrate.lookup'),
      $container->get('migrate.stub'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $uri = $value;
    if (is_array($value)) {
      $attributes = unserialize($value['attributes'], ['allowed_classes' => FALSE]);
      // Drupal 6/7 link attributes might be double serialized.
      if (!is_array($attributes)) {
        $attributes = unserialize($attributes, ['allowed_classes' => FALSE]);
      }

      // In rare cases Drupal 6/7 link attributes are triple serialized.
      // To avoid further problems with them we set them to an empty array
      // in this case.
      if (!is_array($attributes)) {
        $attributes = [];
      }
      // Massage the values into the correct form for the link.
      $uri = $value['url'];
    }

    if (!UrlHelper::isExternal($uri) && preg_match('/node\/(\d+)/', $uri, $matches)) {
      $source_nid = $matches[1];

      $destination_id = parent::transform($source_nid, $migrate_executable, $row, $destination_property);
      if (!empty($destination_id)) {
        $destination_id = is_array($destination_id) ? reset($destination_id) : $destination_id;
        $uri = is_string($value) ? "entity:node/{$destination_id}" : "internal:/node/{$destination_id}";
      }
      else {
        // Make sure that the node link doesn't fail.
        $uri = "internal:/node/{$source_nid}";
      }
    }

    $uri = str_ireplace('https://www.yukon.ca', '', $uri);
    $uri = str_ireplace('http://www.yukon.ca', '', $uri);
    $uri = str_ireplace('https://yukon.ca', '', $uri);
    $uri = str_ireplace('http://yukon.ca', '', $uri);

    if (is_string($value)) {
      return $uri;
    }

    $route['uri'] = $uri;
    $route['options']['attributes'] = $attributes;
    $route['title'] = $value['title'];
    return $route;
  }

}
