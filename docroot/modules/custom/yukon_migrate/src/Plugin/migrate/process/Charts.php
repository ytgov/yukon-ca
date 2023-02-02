<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\media\Entity\Media;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Adds charts to node content.
 *
 * Example:
 *
 * @code
 * process:
 *   field_charts:
 *     plugin: charts
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "charts",
 * )
 */
class Charts extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $node = $row->getSource();
    $paragraphs = [];
    $nodeId = !empty($node['nid']) ? $node['nid'] : $node['entity_id'];
    $charts = !empty($node['field_charts']) ? $node['field_charts'] : NULL;
    if (!empty($charts)) {
      foreach ($charts as $chart) {
        $connection = Database::getConnection('default', 'migrate');
        $query = $connection->select('paragraphs_item', 'fci')
          ->fields('fci', [
            'item_id',
            'field_name',
            'revision_id',
          ]);

        $query->innerJoin('field_data_field_charts', 'fc', 'fc.field_charts_value = fci.item_id');
        $query->innerJoin('field_data_field_chart_data', 'fcd', 'fcd.entity_id = fci.item_id');
        $query->innerJoin('field_data_field_chart_description', 'fcde', 'fcde.entity_id = fci.item_id');
        $query->innerJoin('field_data_field_highlighted_stat', 'fhs', 'fhs.entity_id = fci.item_id');
        $query->innerJoin('field_data_field_source_notes', 'fsn', 'fsn.entity_id = fci.item_id');
        $query->fields('fc',
          [
            'entity_type',
            'bundle',
            'entity_id',
            'field_charts_value',
          ]);
        $query->fields('fcd',
          ['field_chart_data_value',
            'language',
          ]);
        $query->fields('fcde',
          ['field_chart_description_value']);
        $query->fields('fhs',
          ['field_highlighted_stat_value']);
        $query->fields('fsn',
          ['field_source_notes_value']);
        $query->condition('fci.item_id', $chart['value']);
        $results = $query->execute()->fetchAll();
      }

      if (!empty($results)) {
        foreach ($results as $result) {
          \Drupal::logger('test')->info('<pre><code>' . print_r($result, TRUE) . '</code></pre>');
          $paragraph = \Drupal::service('entity_type.manager')
            ->getStorage('paragraph')
            ->loadByProperties([
              'type' => 'charts',
              'parent_id' => $nodeId,
              'parent_type' => 'node',
            ]);
          $paragraph = reset($paragraph);

          if (!$paragraph) {
            $paragraph = Paragraph::create([
              'type' => 'charts',
              'parent_id' => $nodeId,
              'parent_type' => 'node',
            ]);
            $paragraph->save();
          }

          $paragraph->field_chart_data->value = $result->field_chart_data_value;
          $paragraph->field_chart_data->format = 'wysiwyg_ckeditor';
          $paragraph->field_chart_description->value = $result->field_chart_description_value;
          $paragraph->field_chart_description->format = 'wysiwyg_ckeditor';
          $paragraph->field_highlighted_stat->value = $result->field_highlighted_stat_value;
          $paragraph->field_source_notes->value = $result->field_source_notes_value;
          $paragraph->field_source_notes->format = 'wysiwyg_ckeditor';
          $paragraph->save();

          $paragraphs[] = $paragraph;
        }
      }

      return $paragraphs;
    }
  }

}
