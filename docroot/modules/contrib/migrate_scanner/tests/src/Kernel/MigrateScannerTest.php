<?php

namespace Drupal\Tests\migrate_scanner\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Migrate scanner test.
 *
 * @group migrate_scanner
 */
class MigrateScannerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'migrate_scanner',
    'migrate_scanner_test',
  ];

  /**
   * Tests recursive migration discovery.
   */
  public function testRecursiveMigrationDiscovery() {
    /** @var \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.migration');

    $definitions = $plugin_manager->getDefinitions();
    $this->assertArrayHasKey('migration_top_level', $definitions);
    $this->assertArrayHasKey('migration_second_level', $definitions);
    $this->assertArrayHasKey('migration_third_level', $definitions);
  }

}
