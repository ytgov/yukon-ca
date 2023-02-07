<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\media\Entity\Media;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

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
    $originalField = '';
    if (strpos($destination_property, '_icon_') !== FALSE) {
      $originalField = $destination_property;
      if ($destination_property === 'field_icon_dark') {
        $destination_property = 'field_svg_upload';
      }
      if ($destination_property === 'field_icon_light') {
        $destination_property = 'field_light_svg_upload';
      }
    }
    $imageField = !empty($node[$destination_property]) ? $node[$destination_property][0] : $node[$originalField][0];
    if ($node['type'] === 'event' && $destination_property === 'field_featured_image') {
      $imageField = !empty($node['field_feature_image']) ? $node['field_feature_image'][0] : NULL;
      $destination_property = 'field_feature_image';
    }
    if (!empty($imageField) && ($destination_property === 'field_svg_upload' || $destination_property === 'field_light_svg_upload' || $destination_property === 'field_featured_image' || $destination_property === 'field_feature_image')) {
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
        [
          'fid',
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

          $imageMedia = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties([
            'name' => $result->filename,
            'field_media_image' => ['target_id' => $result->$fid],
          ]);
          $imageMedia = reset($imageMedia);

          if (empty($imageMedia)) {
            if (!empty($originalField)) {
              $bundle = 'icon';
            }
            else {
              $bundle = 'image';
            }
            // Create Media.
            $imageMedia = Media::create([
              'name' => $result->filename,
              'bundle' => $bundle,
              'uid' => 1,
              'langcode' => $node['language'],
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

}
