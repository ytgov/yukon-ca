<?php

namespace Drupal\single_content_sync;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\file\FileInterface;

interface ContentFileGeneratorInterface {

  /**
   * Generate a YAML file with the exported content.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object.
   * @param bool $extract_translations
   *   Whether to extract translations.
   *
   * @return \Drupal\file\FileInterface
   *   The generated file represented as object.
   */
  public function generateYamlFile(FieldableEntityInterface $entity, bool $extract_translations = FALSE): FileInterface;

  /**
   * Generate a Zip file with the exported content and assets.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object.
   * @param bool $extract_translations
   *   Whether to extract translations.
   *
   * @return \Drupal\file\FileInterface
   *   The generated file represented as object.
   */
  public function generateZipFile(FieldableEntityInterface $entity, bool $extract_translations = FALSE): FileInterface;

  /**
   * Generate a Zip file with bulk exported content and assets.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface[] $entities
   *   An array of content entities.
   * @param bool $extract_translations
   *   Whether to extract translations.
   * @param bool $extract_assets
   *   Whether to extract assets.
   *
   * @return \Drupal\file\FileInterface
   *   The generated file represented as object.
   */
  public function generateBulkZipFile(array $entities, bool $extract_translations = FALSE, bool $extract_assets = FALSE): FileInterface;

}
