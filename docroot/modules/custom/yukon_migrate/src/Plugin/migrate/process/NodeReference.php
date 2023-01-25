<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * Adds node content to entity reference fields.
 *
 * Example:
 *
 * @code
 * process:
 *   field_primary_item_blocks:
 *     plugin: node_reference
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "node_reference",
 * )
 */
class NodeReference extends ProcessPluginBase {

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
        $fieldReferenceNode = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadByProperties([
            'nid' => $id,
          ]);
        $fieldReferenceNode = reset($fieldReferenceNode);

        if ($fieldReferenceNode) {
          $fieldReferenceData[] = ['target_id' => $fieldReferenceNode->id()];
        }
      }

      return $fieldReferenceData;
    }
  }

}
