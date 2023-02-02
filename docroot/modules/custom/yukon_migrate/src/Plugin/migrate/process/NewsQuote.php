<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Adds news quote to node content.
 *
 * Example:
 *
 * @code
 * process:
 *   field_news_quote:
 *     plugin: newsquote
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "newsquote",
 * )
 */
class NewsQuote extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $node = $row->getSource();
    $nodeId = !empty($node['nid']) ? $node['nid'] : $node['entity_id'];
    $newsQuotes = !empty($node['field_news_quote']) ? $node['field_news_quote'] : NULL;
    $paragraphs = [];
    $results = [];
    if (!empty($newsQuotes)) {
      foreach ($newsQuotes as $newsQuote) {
        $connection = Database::getConnection('default', 'migrate');
        $query = $connection->select('paragraphs_item', 'fci')
          ->fields('fci', [
            'item_id',
            'field_name',
            'revision_id',
          ]);

        $query->innerJoin('field_data_field_news_quote', 'nq', 'nq.field_news_quote_value = fci.item_id');
        $query->innerJoin('field_data_field_quote', 'fq', 'fq.entity_id = fci.item_id');
        $query->innerJoin('field_data_field_quote_source', 'fqs', 'fqs.entity_id = fci.item_id');
        $query->fields('nq',
          [
            'entity_type',
            'bundle',
            'entity_id',
            'field_news_quote_value',
          ]);
        $query->fields('fq',
          [
            'field_quote_value',
            'language',
          ]);
        $query->fields('fqs',
          ['field_quote_source_value']);
        $query->condition('fci.item_id', $newsQuote['value']);
        $query->groupBy('field_quote_value');
        $results = $query->execute()->fetchAll();
      }

      if (!empty($results)) {
        foreach ($results as $result) {
          if ($node['language'] === $result->language) {
            $paragraph = \Drupal::service('entity_type.manager')
              ->getStorage('paragraph')
              ->loadByProperties([
                'type' => 'quotes',
                'parent_id' => $nodeId,
                'parent_type' => 'node',
              ]);
            $paragraph = reset($paragraph);

            if (!$paragraph) {
              $paragraph = Paragraph::create([
                'type' => 'quotes',
                'parent_id' => $nodeId,
                'parent_type' => 'node',
              ]);
              $paragraph->save();
            }

            $paragraph->field_quote = [
              'value' => $result->field_quote_value,
              'format' => 'wysiwyg_ckeditor',
            ];
            $paragraph->field_quote_source = [
              'value' => $result->field_quote_source_value,
              'format' => 'wysiwyg_ckeditor',
            ];
            $paragraph->save();

            $paragraphs[] = $paragraph;
          }
        }
      }
    }

    return $paragraphs;
  }

}
