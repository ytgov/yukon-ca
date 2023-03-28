<?php

namespace Drupal\yukon_migrate\Plugin\migrate\source;

use Drupal\file\Plugin\migrate\source\d7\File;
use Drupal\migrate\Row;

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

      $query->condition('type', $types, 'IN');
    }
    if (isset($this->configuration['file_mime'])) {
      $mimeTypes = $this->configuration['file_mime'];
      if (!is_array($mimeTypes)) {
        $mimeTypes = [$mimeTypes];
      }

      $query->condition('filemime', $mimeTypes, 'IN');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($this->moduleExists('file_entity')) {
      $fid = $row->getSourceProperty('fid');

      $entity_translatable = $this->isEntityTranslatable('file');
      $source_language = $this->getEntityTranslationSourceLanguage('file', $fid);
      $language = $entity_translatable && $source_language ? $source_language : 'en';

      $row->setSourceProperty('source_language', $language);
    }

    return parent::prepareRow($row);
  }

}
