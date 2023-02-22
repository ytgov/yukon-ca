<?php

namespace Drupal\yukon_migrate\Plugin\migrate\source;

use Drupal\file\Plugin\migrate\source\d7\File;

/**
 * The 'yukon_migrate_file_mime' source plugin.
 *
 * @MigrateSource(
 *   id = "yukon_migrate_file_mime",
 *   source_module = "file"
 * )
 */
class YukonFilemime extends File {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    if (isset($this->configuration['file_mime'])) {
      $mimeTypes = $this->configuration['file_mime'];
      if (!is_array($mimeTypes)) {
        $mimeTypes = [$mimeTypes];
      }

      $query->condition('filemime', $mimeTypes);
    }
    return $query;
  }

}
