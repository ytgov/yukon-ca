<?php

namespace Drupal\single_content_sync;

class ContentBatchImporter {

  /**
   * Get content importer service.
   *
   * @return \Drupal\single_content_sync\ContentImporterInterface
   *   The content importer.
   */
  public static function contentImporter(): ContentImporterInterface {
    return \Drupal::service('single_content_sync.importer');
  }

  /**
   * Import content operation.
   */
  public static function batchImportFile($original_file_path, &$context): void {
    $context['results'][] = self::contentImporter()->importFromFile($original_file_path);
  }

  /**
   * Import assets operation.
   */
  public static function batchImportAssets(string $extracted_file_path, string $zip_file_path, &$context): void {
    self::contentImporter()->importAssets($extracted_file_path, $zip_file_path);
  }

  /**
   * Clean import directory after before finish batch.
   */
  public static function cleanImportDirectory(string $import_directory, &$context): void {
    \Drupal::service('file_system')->deleteRecursive($import_directory);
  }

  /**
   * Batch finished callback.
   */
  public static function batchImportFinishedCallback($success, $results, $operations): void {
    if ($success) {
      \Drupal::service('messenger')->addMessage(t('The import of content was processed successfully'));
    }
    else {
      \Drupal::service('messenger')->addError(t('The import process finished with an error.'));
    }
  }

}
