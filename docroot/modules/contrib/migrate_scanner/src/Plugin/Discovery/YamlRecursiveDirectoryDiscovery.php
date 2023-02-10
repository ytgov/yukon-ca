<?php

namespace Drupal\migrate_scanner\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\YamlDirectoryDiscovery;
use Drupal\migrate_scanner\Component\Discovery\YamlRecursiveDirectoryDiscovery as ComponentYamlDirectoryDiscovery;

/**
 * Allows multiple YAML files per directory to define plugin definitions.
 */
class YamlRecursiveDirectoryDiscovery extends YamlDirectoryDiscovery {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $directories, $file_cache_key_suffix, $key = 'id', array $patterns = []) {
    // Intentionally does not call parent constructor as this class uses a
    // different YAML discovery.
    $this->discovery = new ComponentYamlDirectoryDiscovery($directories, $file_cache_key_suffix, $key, $patterns);
  }

}
