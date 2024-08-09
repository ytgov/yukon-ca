<?php

namespace Drupal\yukon_migrate\Drush\Commands;

use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class YukonMigrateOrdered extends DrushCommands {

  /**
   * Migration plugin manager service.
   */
  protected MigrationPluginManagerInterface $migrationPluginManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager service.
   */
  public function __construct(MigrationPluginManagerInterface $migration_plugin_manager) {
    parent::__construct();
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * Dependency injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container for the application.
   *
   * @return self
   *   A new instance of the class.
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * Migrate all migrations in order of dependencies.
   */
  #[CLI\Command(name: 'yukon_migrate:migrate_all', aliases: ['yma'])]
  #[CLI\Usage(name: 'yukon_migrate:migrate_all', description: 'Migrates all migrations in order of dependencies.')]
  public function migrateAll() {
    $migrations = $this->orderMigrations();
    foreach ($migrations as $migration) {
      $this->output()->writeln('echo "Migrating ' . $migration . '..."');
      $this->output()->writeln('time $COMMAND migrate:import --continue-on-failure ' . $migration);
      $this->output()->writeln('');
    }
  }

  /**
   * Orders migrations based on their dependencies.
   *
   * @return array
   *   An ordered array of migration IDs.
   */
  protected function orderMigrations() {
    $ordered_migrations = [];
    $unprocessed = $this->migrationPluginManager->createInstancesByTag('Ordered Migration');

    $processed = [];

    while (!empty($unprocessed)) {
      $progress = FALSE;

      foreach ($unprocessed as $migration_id => $migration) {
        /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
        $dependencies = $migration->getMigrationDependencies()['required'];

        // Check if all dependencies have been processed.
        if (empty(array_diff($dependencies, $processed))) {
          $ordered_migrations[] = $migration_id;
          $processed[] = $migration_id;
          unset($unprocessed[$migration_id]);
          $progress = TRUE;
        }
      }

      if (!$progress) {
        throw new \RuntimeException('Circular dependency detected among migrations.');
      }
    }

    return $ordered_migrations;
  }

}
