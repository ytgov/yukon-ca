<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\media\Entity\Media;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * Adds taxonomy terms to node content.
 *
 * Example:
 *
 * @code
 * process:
 *   field_department_term:
 *     plugin: imagemedia
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "imagemedia",
 * )
 */
class ImageMedia extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $node = $row->getSource();
    $imageField = !empty($node[$destination_property]) ? $node[$destination_property][0] : NULL;
    if ($imageField !== NULL && ($destination_property === 'field_svg_upload' || $destination_property === 'field_light_svg_upload')) {
      $connection = Database::getConnection('default', 'migrate');
      $query = $connection->select('field_data_' . $destination_property, 'svg')
        ->fields('svg', [
          $destination_property . '_fid',
          $destination_property . '_alt',
          $destination_property . '_title',
          $destination_property . '_width',
          $destination_property . '_height',
        ]);
      $query->innerJoin('file_managed', 'fm', 'fm.fid = svg. ' . $destination_property . '_fid');
      $query->fields('fm',
        ['fid',
          'filename',
          'uri',
        ]);
      $query->condition('svg.' . $destination_property . '_fid', $imageField['fid']);
      $results = $query->execute()->fetchAll();

      if (!empty($results)) {

        foreach ($results as $result) {
          $fid = $destination_property . '_fid';
          $alt = $destination_property . '_alt';
          $title = $destination_property . '_title';

          // Create Media.
          $imageMedia = Media::create([
            'name' => $result->filename,
            'bundle' => 'image',
            'uid' => 1,
            'langcode' => 'en',
            'status' => 1,
            'field_media_image' => [
              'target_id' => $result->$fid,
              'alt' => $result->$alt,
              'title' => $result->$title,
            ],
          ]);
          $imageMedia->save();
        }

        return $imageMedia->id();
      }
    }
  }

}
