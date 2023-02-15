<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Adds node content to entity reference fields.
 *
 * Example:
 *
 * @code
 * process:
 *   field_primary_item_blocks:
 *     plugin: yukon_node_reference
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "yukon_node_reference",
 * )
 */
class NodeReference extends YGMigratePluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $node = $row->getSource();
    $fieldReference = !empty($node[$destination_property]) ? $node[$destination_property] : NULL;
    if (!empty($fieldReference)) {
      if (!is_array($fieldReference)) {
        $fieldReference = explode(',', $fieldReference);
      }
      $fieldReferenceData = [];

      foreach ($fieldReference as $id) {
        $fieldReferenceNode = $this->entityTypeManager
          ->getStorage('node')
          ->loadByProperties([
            'nid' => $id,
          ]);
        $fieldReferenceNode = reset($fieldReferenceNode);

        if ($fieldReferenceNode) {
          $fieldReferenceData[] = ['target_id' => $fieldReferenceNode->id()];
        }
        else {
          $connection = Database::getConnection('default', 'migrate');
          $query = $connection->select('node', 'n')
            ->fields('n', [
              'nid',
              'title',
              'type',
              'language',
            ]);
          $query->condition('n.nid', $id);

          $result = $query->execute()->fetchObject();

          if ($result) {
            $node = Node::create([
              'title' => $result->title,
              'type' => $result->type,
              'langcode' => $result->language,
            ]);
            $node->save();

            $fieldReferenceData[] = ['target_id' => $node->id()];
          }
        }
      }

      return $fieldReferenceData;
    }
  }

}
