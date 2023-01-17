<?php

namespace Drupal\single_content_sync;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;

interface ContentExporterInterface {

  /**
   * Export node as array.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to export.
   *
   * @return array
   *   The exported content as array.
   */
  public function doExportToArray(FieldableEntityInterface $entity): array;

  /**
   * Export node to YAML format.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to export.
   * @param bool $extract_translations
   *   Whether to extract translations.
   *
   * @return string
   *   The exported content in YML format.
   */
  public function doExportToYml(FieldableEntityInterface $entity, bool $extract_translations = FALSE): string;

  /**
   * Get field value in the proper format for further importing.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field item list.
   *
   * @return array|string|bool
   *   The formatted field value.
   */
  public function getFieldValue(FieldItemListInterface $field);

  /**
   * Export base fields of the entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to export.
   *
   * @return array
   *   Exported content of the entity's base fields.
   */
  public function exportBaseValues(FieldableEntityInterface $entity): array;

  /**
   * Export custom fields of the entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to export.
   * @param bool $check_translated_fields_only
   *   Whether to check only translatable fields.
   *
   * @return array
   *   Exported content of the entity's custom fields.
   */
  public function exportCustomValues(FieldableEntityInterface $entity, bool $check_translated_fields_only = FALSE): array;

}
