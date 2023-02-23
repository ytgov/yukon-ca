<?php

namespace Drupal\yukon_migrate\Plugin\migrate\source;

use Drupal\file\Plugin\migrate\source\d7\File;

/**
 * The 'yukon_migrate_file' source plugin.
 *
 * @MigrateSource(
 *   id = "yukon_migrate_file",
 *   source_module = "file"
 * )
 */
class YukonFile extends File {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    if (isset($this->configuration['file_type'])) {
      $types = $this->configuration['file_type'];
      if (!is_array($types)) {
        $types = [$types];
      }

      $query->condition('type', $types);
    }
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
