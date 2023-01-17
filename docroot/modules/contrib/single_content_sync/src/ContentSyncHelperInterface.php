<?php

namespace Drupal\single_content_sync;

use Drupal\Core\Archiver\ArchiverInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\file\FileInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

interface ContentSyncHelperInterface {

  /**
   * Prepare a directory to be used for importing or exporting content into.
   *
   * @param string $directory
   *   The directory to be prepared.
   */
  public function prepareFilesDirectory(string &$directory): void;

  /**
   * Save a file content temporary into the local file destination.
   *
   * @param string $content
   *   The content of the file.
   * @param string $destination
   *   The desired file destination.
   *
   * @return \Drupal\file\FileInterface
   *   The saved file entity object.
   */
  public function saveFileContentTemporary(string $content, string $destination): FileInterface;

  /**
   * Generates a directory for content to be imported from.
   *
   * @return string
   *   The path to the generated directory
   */
  public function createImportDirectory(): string;

  /**
   * Get default file scheme.
   *
   * @return string
   *   The name of the default file scheme.
   */
  public function getDefaultFileScheme(): string;

  /**
   * Create a zip instance object from the real file path.
   *
   * @param string $file_real_path
   *   The real path to the local file.
   *
   * @return \Drupal\Core\Archiver\ArchiverInterface
   *   The zip object.
   */
  public function createZipInstance(string $file_real_path): ArchiverInterface;

  /**
   * Generates a file name based on an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return string
   *   The generated file name.
   */
  public function generateContentFileName(EntityInterface $entity): string;

  /**
   * Validate YAML file content.
   *
   * @param string $content
   *   The YAML content.
   *
   * @return array
   *   The decoded YAML content as array.
   */
  public function validateYamlFileContent(string $content): array;

  /**
   * Get real path of the file by file id.
   *
   * @param int $fid
   *   The file id.
   *
   * @return string
   *   The real path of the file.
   */
  public function getFileRealPathById(int $fid): string;

  /**
   * Get an entity object from the default language configuration.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $parameters
   *   The parameters from which to get the entity object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity interface from the default language configuration.
   */
  public function getDefaultLanguageEntity(ParameterBag $parameters): EntityInterface;

  /**
   * Returns TRUE or FALSE based on the configuration of the module.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   *
   * @return bool
   *   Returns TRUE if the entity is allowed to be exported,
   *   else returns FALSE.
   */
  public function access(EntityInterface $entity): bool;

}
