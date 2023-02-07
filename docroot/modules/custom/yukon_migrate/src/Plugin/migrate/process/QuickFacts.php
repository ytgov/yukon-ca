<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Adds quick facts to node content.
 *
 * Example:
 *
 * @code
 * process:
 *   field_quick_facts:
 *     plugin: yg_quickfacts
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "yg_quickfacts",
 * )
 */
class QuickFacts extends YGMigratePluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $node = $row->getSource();
    $paragraph = [];
    $nodeId = !empty($node['nid']) ? $node['nid'] : $node['entity_id'];
    $quickFactsField = !empty($node[$destination_property]) ? $node[$destination_property][0] : NULL;
    if (!empty($quickFactsField)) {
      $connection = Database::getConnection('default', 'migrate');
      $query = $connection->select('paragraphs_item', 'fci')
        ->fields('fci', [
          'item_id',
          'field_name',
          'revision_id',
        ]);

      $query->innerJoin('field_data_field_quick_facts', 'qf', 'qf.field_quick_facts_value = fci.item_id');
      $query->innerJoin('field_data_field_facts', 'fc', 'fc.entity_id = fci.item_id');
      $query->innerJoin('node', 'n', 'n.nid = fc.entity_id');
      $query->fields('qf',
        [
          'entity_type',
          'bundle',
          'entity_id',
          'field_quick_facts_value',
        ]);
      $query->fields('fc',
        ['field_facts_value']);
      $query->fields('n',
        [
          'nid',
          'title',
          'type',
        ]);
      $query->condition('fci.item_id', $quickFactsField['value']);
      $result = $query->execute()->fetchObject();

      if (!empty($result)) {
        $paragraph = $this->entityTypeManager
          ->getStorage('paragraph')
          ->loadByProperties([
            'type' => 'quick_facts',
            'parent_id' => $nodeId,
            'parent_type' => 'node',
          ]);
        $paragraph = reset($paragraph);

        if (!$paragraph) {
          $paragraph = Paragraph::create([
            'type' => 'quick_facts',
            'parent_id' => $nodeId,
            'parent_type' => 'node',
          ]);
          $paragraph->save();
        }

        $paragraph->field_facts->value = $result->field_facts_value;
        $paragraph->field_facts->format = 'wysiwyg_ckeditor';
        $paragraph->save();
      }

      return $paragraph;
    }
  }

}
