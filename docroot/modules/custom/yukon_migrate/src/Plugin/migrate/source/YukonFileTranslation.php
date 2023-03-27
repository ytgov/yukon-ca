<?php

namespace Drupal\yukon_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * The 'yukon_migrate_file_translation' source plugin.
 *
 * @MigrateSource(
 *   id = "yukon_migrate_file_translation",
 *   source_module = "entity_translation"
 * )
 */
class YukonFileTranslation extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('entity_translation', 'et')
      ->fields('et')
      ->fields('f', [
        'filename',
        'type',
      ])
      ->condition('et.entity_type', 'file')
      ->condition('et.source', '', '<>');

    $query->innerJoin('file_managed', 'f', '[f].[fid] = [et].[entity_id]');

    if (isset($this->configuration['file_type'])) {
      $query->condition('f.type', (array) $this->configuration['file_type'], 'IN');
    }
    if (isset($this->configuration['file_mime'])) {
      $query->condition('f.filemime', (array) $this->configuration['file_mime'], 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'entity_type' => $this->t('The entity type this translation relates to'),
      'entity_id' => $this->t('The entity ID this translation relates to'),
      'language' => $this->t('The target language for this translation.'),
      'source' => $this->t('The source language from which this translation was created.'),
      'uid' => $this->t('The author of this translation.'),
      'status' => $this->t('Boolean indicating whether the translation is published (visible to non-administrators).'),
      'translate' => $this->t('A boolean indicating whether this translation needs to be updated.'),
      'created' => $this->t('The Unix timestamp when the translation was created.'),
      'changed' => $this->t('The Unix timestamp when the translation was most recently saved.'),
      'fid' => $this->t('The file ID this translation relates to'),
      'filename' => $this->t('The original name of the file.'),
      'langcode' => $this->t('The language for current translation.'),
      'type' => $this->t('The type this translation relates to'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // File Entity module makes files as fieldable entities, so we can get
    // Field API field values.
    if ($this->moduleExists('file_entity')) {
      $type = $row->getSourceProperty('type');
      $fid = $row->getSourceProperty('entity_id');
      $vid = $row->getSourceProperty('revision_id');

      // If this entity was translated using Entity Translation, we need to get
      // its source language to get the field values in the right language.
      $entity_translatable = $this->isEntityTranslatable('file');
      $language = $row->getSourceProperty('language');

      foreach ($this->getFields('file', $type) as $field_name => $field) {
        // Ensure we're using the right language if the entity and the field are
        // translatable.
        $field_language = $entity_translatable && $field['translatable'] ? $language : NULL;
        $row->setSourceProperty($field_name, $this->getFieldValues('file', $field_name, $fid, $vid, $field_language));
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_id' => [
        'type' => 'integer',
        'alias' => 'et',
      ],
      'language' => [
        'type' => 'string',
        'alias' => 'et',
      ],
    ];
  }

}
