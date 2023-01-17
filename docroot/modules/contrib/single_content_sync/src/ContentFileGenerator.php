<?php

namespace Drupal\single_content_sync;

use Drupal\Core\Archiver\ArchiverInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\file\FileInterface;

class ContentFileGenerator implements ContentFileGeneratorInterface {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The content sync helper.
   *
   * @var \Drupal\single_content_sync\ContentSyncHelperInterface
   */
  protected $contentSyncHelper;

  /**
   * The content exporter.
   *
   * @var \Drupal\single_content_sync\ContentExporterInterface
   */
  protected $contentExporter;

  /**
   * The private temp store of the module.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * ContentFileGenerator constructor.
   *
   * @param \Drupal\Core\file\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\single_content_sync\ContentSyncHelperInterface $content_sync_helper
   *   The content sync helper.
   * @param \Drupal\single_content_sync\ContentExporterInterface $content_exporter
   *   The content exporter.
   * @param \Drupal\Core\TempStore\PrivateTempStore $store
   *   The private temp store of the module.
   */
  public function __construct(FileSystemInterface $file_system, ContentSyncHelperInterface $content_sync_helper, ContentExporterInterface $content_exporter, PrivateTempStore $store) {
    $this->fileSystem = $file_system;
    $this->contentSyncHelper = $content_sync_helper;
    $this->contentExporter = $content_exporter;
    $this->privateTempStore = $store;
  }

  /**
   * {@inheritdoc}
   */
  public function generateYamlFile(FieldableEntityInterface $entity, bool $extract_translations = FALSE): FileInterface {
    $output = $this->contentExporter->doExportToYml($entity, $extract_translations);
    $default_scheme = $this->contentSyncHelper->getDefaultFileScheme();
    $directory = "{$default_scheme}://export";
    $file_name = $this->contentSyncHelper->generateContentFileName($entity);
    $this->contentSyncHelper->prepareFilesDirectory($directory);

    return $this->contentSyncHelper->saveFileContentTemporary($output, "{$directory}/{$file_name}.yml");
  }

  /**
   * {@inheritdoc}
   */
  public function generateZipFile(FieldableEntityInterface $entity, bool $extract_translations = FALSE): FileInterface {
    $export_file = $this->generateYamlFile($entity, $extract_translations);

    // Generate an empty zip file to be used for storing the exported content.
    $zip_name = $this->contentSyncHelper->generateContentFileName($entity);
    $zip_file = $this->generateEmptyZipFile($zip_name);

    $zip_file_path = $this->fileSystem->realpath($zip_file->getFileUri());
    $zip = $this->contentSyncHelper->createZipInstance($zip_file_path);

    // Add exported content to the zip file.
    $content_file_path = $this->fileSystem->realpath($export_file->getFileUri());
    $zip->getArchive()->addFile($content_file_path, $export_file->getFileName());

    // Add assets such images and files to the zip file.
    $this->addAssetsToZip($zip);

    return $zip_file;
  }

  /**
   * {@inheritdoc}
   */
  public function generateBulkZipFile(array $entities, bool $extract_translations = FALSE, bool $extract_assets = FALSE): FileInterface {
    // Generate an empty zip file to be used for storing the exported content.
    $zip_name = sprintf('content-bulk-export-%s', date('d_m_Y-H_i'));
    $zip_file = $this->generateEmptyZipFile($zip_name);

    $zip_file_path = $this->fileSystem->realpath($zip_file->getFileUri());
    $zip = $this->contentSyncHelper->createZipInstance($zip_file_path);

    // Clean up storage with assets before we start exporting a content.
    $this->privateTempStore->delete('export.assets');

    // Fill the zip with content and assets.
    foreach ($entities as $entity) {
      // Generate the yml files and add to the zip.
      $export_file = $this->generateYamlFile($entity, $extract_translations);
      $content_file_path = $this->fileSystem->realpath($export_file->getFileUri());
      $zip->getArchive()->addFile($content_file_path, $export_file->getFilename());
    }

    if ($extract_assets) {
      $this->addAssetsToZip($zip);
    }

    return $zip_file;
  }

  /**
   * Generate an empty zip folder.
   *
   * @param string $name
   *   String containing the name of the zip file to generate.
   *
   * @return \Drupal\file\FileInterface
   *   The generated empty zip file.
   */
  protected function generateEmptyZipFile(string $name): FileInterface {
    $default_scheme = $this->contentSyncHelper->getDefaultFileScheme();
    $directory = "{$default_scheme}://export/zip";
    $this->contentSyncHelper->prepareFilesDirectory($directory);

    return $this->contentSyncHelper->saveFileContentTemporary('', "{$directory}/{$name}.zip");
  }

  /**
   * Add assets to zip file.
   *
   * @param \Drupal\Core\Archiver\ArchiverInterface $zip
   *   The zip file to which the assets will be added.
   */
  protected function addAssetsToZip(ArchiverInterface $zip): void {
    $assets = $this->privateTempStore->get('export.assets') ?? [];

    foreach ($assets as $file_uri) {
      // Add file to the zip.
      $file_full_path = $this->fileSystem->realpath($file_uri);
      $file_relative_path = explode('://', $file_uri)[1];
      $zip->getArchive()->addFile($file_full_path, "assets/{$file_relative_path}");
    }

    // Clean up the storage after we exported assets to the zip.
    $this->privateTempStore->delete('export.assets');
  }

}
