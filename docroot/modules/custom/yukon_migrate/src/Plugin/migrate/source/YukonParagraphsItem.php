<?php

namespace Drupal\yukon_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\paragraphs\Plugin\migrate\source\d7\ParagraphsItem;

/**
 * Paragraphs Item source plugin with translation option.
 *
 * @MigrateSource(
 *   id = "yukon_paragraphs_item",
 *   source_module = "paragraphs",
 * )
 */
class YukonParagraphsItem extends ParagraphsItem {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    parent::prepareRow($row);
    [
      'item_id' => $paragraph_id,
      'revision_id' => $paragraph_revision_id,
      'bundle' => $bundle,
    ] = $row->getSource();

    $entity_translatable = $this->isEntityTranslatable('paragraphs_item');
    $source_language = $this->getEntityTranslationSourceLanguage('paragraphs_item', $paragraph_id);
    if ($entity_translatable && !empty($row->getSourceProperty('constants'))) {
      $source_language = $row->getSourceProperty('constants')['translation_language'];
    }

    // Get Field API field values.
    foreach (array_keys($this->getFields('paragraphs_item', $bundle)) as $field_name) {
      $value = $this->getFieldValues('paragraphs_item', $field_name, $paragraph_id, $paragraph_revision_id, $source_language);
      !empty($value) ?: $value = $this->getFieldValues('paragraphs_item', $field_name, $paragraph_id, $paragraph_revision_id);
      $row->setSourceProperty($field_name, $value);
    }
    return TRUE;
  }

}
