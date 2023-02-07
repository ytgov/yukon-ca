<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Adds paragraph to node content.
 *
 * Example:
 *
 * @code
 * process:
 *   field_secondary_content:
 *     plugin: yg_paragraph_secondary_content
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "yg_paragraph_secondary_content",
 * )
 */
class ParagraphSecondaryContent extends YGMigratePluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $node = $row->getSource();
    $nodeId = !empty($node['nid']) ? $node['nid'] : $node['entity_id'];
    $paragraphField = !empty($node['field_secondary_content']) ? $node['field_secondary_content'] : NULL;
    if (!empty($paragraphField)) {
      foreach ($paragraphField as $paragraph) {
        $connection = Database::getConnection('default', 'migrate');
        $query = $connection->select('paragraphs_item', 'fci')
          ->fields('fci', [
            'item_id',
            'field_name',
            'revision_id',
          ]);

        $query->innerJoin('field_data_field_secondary_content', 'fd', 'fd.field_secondary_content_value = fci.item_id');
        $query->innerJoin('field_data_field_use_landing_page_level_2_a', 'fdh', 'fdh.entity_id = fci.item_id');
        $query->innerJoin('field_data_field_category_title', 'fdct', 'fdct.entity_id = fci.item_id');
        $query->innerJoin('field_data_field_subcategory_links', 'fdsl', 'fdsl.entity_id = fci.item_id');
        $query->innerJoin('field_data_field_landing_page_level_2', 'fdlp', 'fdlp.entity_id = fci.item_id');
        $query->innerJoin('node', 'n', 'n.nid = fdlp.field_landing_page_level_2_target_id');
        $query->fields('fd',
          [
            'entity_type',
            'bundle',
            'entity_id',
            'field_secondary_content_revision_id',
          ]);
        $query->fields('fdh',
          ['field_use_landing_page_level_2_a_value']);
        $query->fields('fdct',
          ['field_category_title_value']);
        $query->fields('fdsl',
          [
            'field_subcategory_links_url',
            'field_subcategory_links_title',
          ]);
        $query->fields('fdlp',
          ['field_landing_page_level_2_target_id']);
        $query->fields('n',
          [
            'nid',
            'title',
            'type',
          ]);
        $query->condition('fci.item_id', $paragraph['value']);
        $query->addExpression("GROUP_CONCAT(field_subcategory_links_url separator '|')", 'link_url');
        $query->addExpression("GROUP_CONCAT(field_subcategory_links_title separator '|')", 'link_title');
        $query->groupBy('fci.item_id');
        $results[] = $query->execute()->fetchAll();
      }

      if (!empty($results)) {
        $paragraphs = [];
        $reconfiguredData = [];
        $links = [];
        if (!empty($results)) {
          foreach ($results as $result) {
            foreach ($result as $item) {
              $link_url = explode("|", $item->link_url);
              $link_title = explode("|", $item->link_title);
              foreach ($link_url as $i => $uri) {
                foreach ($link_title as $j => $title) {
                  if ($i === $j) {
                    if (strpos($uri, 'http') === FALSE) {
                      $uri = 'internal:/' . $uri;
                    }
                    $links[$i] = [
                      'uri' => $uri,
                      'title' => $title,
                    ];
                  }
                }
              }

              $data = [
                'item_id' => $item->item_id,
                'nid' => $item->nid,
                'title' => $item->title,
                'type' => $item->type,
                'header' => $item->field_use_landing_page_level_2_a_value,
                'category' => $item->field_category_title_value,
                'links' => $links,
              ];

              $reconfiguredData[] = $data;
            }
          }
        }

        foreach ($reconfiguredData as $item) {
          $paragraph = Paragraph::create([
            'type' => 'secondary_content',
            'parent_id' => $nodeId,
            'parent_type' => 'node',
          ]);
          $paragraph->save();

          $referencedNode = $this->entityTypeManager->getStorage('node')->loadByProperties([
            'title' => $item['title'],
            'type' => $item['type'],
          ]);
          $referencedNode = reset($referencedNode);

          if (!empty($referencedNode)) {
            // Populate fields.
            $paragraph->field_use_landing_page_level_2_a->value = $item['header'];
            $paragraph->field_category_title->value = $item['category'];
            $paragraph->field_subcategory_links = $item['links'];
            $paragraph->field_landing_page_level_2->target_id = $referencedNode->id();
            $paragraph->save();

            $paragraphs[] = $paragraph;
          }
        }

        return $paragraphs;
      }
    }
  }

}
