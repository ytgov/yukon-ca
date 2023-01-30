<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Adds paragraph to node content.
 *
 * Example:
 *
 * @code
 * process:
 *   field_primary_content:
 *     plugin: paragraph_primary_content
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "paragraph_primary_content",
 * )
 */
class ParagraphPrimaryContent extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $node = $row->getSource();
    $nodeId = !empty($node['nid']) ? $node['nid'] : $node['entity_id'];
    $paragraphField = !empty($node['field_primary_content']) ? $node['field_primary_content'] : NULL;
    if (!empty($paragraphField)) {
      foreach ($paragraphField as $paragraph) {
        $connection = Database::getConnection('default', 'migrate');
        $query = $connection->select('paragraphs_item', 'fci')
          ->fields('fci', [
            'item_id',
            'field_name',
            'revision_id',
          ]);

        $query->innerJoin('field_data_field_primary_content', 'fd', 'fd.field_primary_content_value = fci.item_id');
        $query->innerJoin('field_data_field_popular_links', 'fdpl', 'fdpl.entity_id = fci.item_id');
        $query->innerJoin('node', 'n', 'n.nid = fdpl.field_popular_links_target_id');
        $query->fields('fd',
          [
            'entity_type',
            'bundle',
            'entity_id',
            'field_primary_content_revision_id',
          ]);
        $query->fields('fdpl',
          ['field_popular_links_target_id']);
        $query->fields('n',
          [
            'nid',
            'title',
            'type',
          ]);
        $query->condition('fci.item_id', $paragraph['value']);
        $results[] = $query->execute()->fetchAll();
      }

      if (!empty($results)) {
        $results = array_filter($results);
        $paragraphs = [];

        foreach ($results as $result) {
          foreach ($result as $item) {
            $paragraph = Paragraph::create([
              'type' => 'primary_content',
              'parent_id' => $nodeId,
              'parent_type' => 'node',
            ]);
            $paragraph->save();

            $referencedNode = \Drupal::service('entity_type.manager')->getStorage('node')->loadByProperties([
              'title' => $item->title,
              'type' => $item->type,
            ]);
            $referencedNode = reset($referencedNode);

            if (!empty($referencedNode)) {
              // Populate fields.
              $paragraph->field_popular_links->target_id = $referencedNode->id();
            }
            $paragraph->save();

            $paragraphs[] = $paragraph;
          }

          return $paragraphs;
        }
      }
    }
  }

}
