<?php

namespace Drupal\Tests\single_content_sync\Functional;

/**
 * Define a trait with useful methods to use in tests.
 */
trait SingleContentSyncImportContentTrait {

  /**
   * Imports a file.
   *
   * @param string $file_name
   *   The name of the file in the assets folder.
   */
  protected function importFile($file_name) {
    $file_path = \Drupal::service('extension.list.module')->getPath('single_content_sync') . '/tests/assets/' . $file_name . '.yml';
    \Drupal::service('single_content_sync.importer')->importFromFile($file_path);
  }

}
