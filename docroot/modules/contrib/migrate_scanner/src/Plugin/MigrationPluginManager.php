<?php

namespace Drupal\migrate_scanner\Plugin;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\migrate\Plugin\Discovery\ProviderFilterDecorator;
use Drupal\migrate\Plugin\MigrationPluginManager as DefaultMigrationPluginManager;
use Drupal\migrate\Plugin\NoSourcePluginDecorator;
use Drupal\migrate_scanner\Plugin\Discovery\YamlRecursiveDirectoryDiscovery;

/**
 * Plugin manager for migration plugins.
 */
class MigrationPluginManager extends DefaultMigrationPluginManager {

  use DependencySerializationTrait;

  /**
   * Gets the plugin discovery.
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $directories = array_map(function ($directory) {
        return [$directory . '/migrations'];
      }, $this->moduleHandler->getModuleDirectories());

      // Retrieve **/*.yml files but gets rid of /migrations/state/*.yml files.
      $patterns = [
        'exclude' => [],
        'include' => [],
      ];
      // Allow modules to alter patterns.
      $this->moduleHandler->alter('migrate_scanner_patterns', $patterns);

      // Retrieves migrations filtered according to given patterns.
      $yaml_discovery = new YamlRecursiveDirectoryDiscovery($directories, 'migrate', 'id', $patterns);
      // This gets rid of migrations which try to use a non-existent source
      // plugin. The common case for this is if the source plugin has, or
      // specifies, a non-existent provider.
      $only_with_source_discovery = new NoSourcePluginDecorator($yaml_discovery);
      // This gets rid of migrations with explicit providers set if one of the
      // providers do not exist before we try to use a potentially non-existing
      // deriver. This is a rare case.
      $filtered_discovery = new ProviderFilterDecorator($only_with_source_discovery, [
        $this->moduleHandler,
        'moduleExists',
      ]);
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($filtered_discovery);
    }
    return $this->discovery;
  }

}
