<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MigratedPathLookup' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "migrated_path_lookup"
 * )
 */
class MigratedPathLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {
  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The migration to be executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

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
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $migration_plugin_manager
   *   The Migration Plugin Manager Interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigratePluginManagerInterface $migration_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->migration = $migration;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $matches = [];
    if (preg_match('/^(node|taxonomy\/term)\/(\d+)$/', $value, $matches)) {
      $id = $matches[2];
      $base_path = $matches[1];

      $config = [
        'migration' => [
          'yukon_migrate_basic_page',
          'yukon_migrate_topics_page',
          'yukon_migrate_landing_page',
          'yukon_migrate_landing_page_level_2',
        ],
      ];

      /** @var \Drupal\migrate\Plugin\migrate\process\MigrationLookup|bool $migration_lookup */
      $migration_lookup = $this->migrationPluginManager->createInstance('migration_lookup', $config, $this->migration);
      if ($migration_lookup) {
        $migrated_id = $migration_lookup->transform($id, $migrate_executable, $row, $destination_property);
        if ($migrated_id) {
          $value = $base_path . '/' . $migrated_id;
        }
      }
    }
    return $value;
  }

}
