<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\media\Entity\Media;

/**
 * Adds paragraph to node content.
 *
 * Example:
 *
 * @code
 * process:
 *   field_image_gallery:
 *     plugin: yg_paragraph_image_gallery
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "yg_paragraph_image_gallery",
 * )
 */
class ParagraphImageGallery extends YGMigratePluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $node = $row->getSource();
    $nodeId = !empty($node['nid']) ? $node['nid'] : $node['entity_id'];
    $paragraph = !empty($node['field_image_gallery']) ? $node['field_image_gallery'] : NULL;
    foreach ($paragraph as $item) {
      $connection = Database::getConnection('default', 'migrate');
      $query = $connection->select('paragraphs_item', 'fci')
        ->fields('fci', [
          'item_id',
          'field_name',
          'revision_id',
        ]);

      $query->innerJoin('field_data_field_image_gallery', 'fd', 'fd.field_image_gallery_value = fci.item_id');
      $query->innerJoin('field_data_title_field', 'fdt', 'fdt.entity_id = fci.item_id');
      $query->innerJoin('field_data_field_description', 'fdd', 'fdd.entity_id = fci.item_id');
      $query->innerJoin('field_data_field_link', 'fdl', 'fdl.entity_id = fci.item_id');
      $query->innerJoin('field_data_field_slide_image', 'fdsi', 'fdsi.entity_id = fci.item_id');
      $query->innerJoin('file_managed', 'fm', 'fm.fid = fdsi.field_slide_image_fid');
      $query->fields('fd',
        [
          'entity_type',
          'bundle',
          'entity_id',
          'field_image_gallery_revision_id',
        ]);
      $query->fields('fdt',
        ['title_field_value']);
      $query->fields('fdd',
        ['field_description_value']);
      $query->fields('fdl',
        [
          'field_link_url',
          'field_link_title',
        ]);
      $query->fields('fdsi',
        [
          'field_slide_image_fid',
          'field_slide_image_alt',
          'field_slide_image_title',
          'field_slide_image_width',
          'field_slide_image_height',
        ]);
      $query->fields('fm',
        [
          'fid',
          'filename',
          'uri',
        ]);
      $query->condition('fci.item_id', $item['value']);
      $results = $query->execute()->fetchAll();
    }

    if (!empty($results)) {
      $paragraphs = [];

      foreach ($results as $result) {
        $paragraph = $this->entityTypeManager->getStorage('paragraph')->loadByProperties([
          'type' => 'image_gallery',
          'parent_id' => $nodeId,
          'parent_type' => 'node',
        ]);
        $paragraph = reset($paragraph);

        if (empty($paragraph)) {
          $paragraph = Paragraph::create([
            'type' => 'image_gallery',
            'parent_id' => $nodeId,
            'parent_type' => 'node',
          ]);
          $paragraph->save();
        }

        $imageMedia = $this->entityTypeManager->getStorage('media')->loadByProperties([
          'name' => $result->filename,
          'field_media_image' => ['target_id' => $result->field_slide_image_fid],
        ]);
        $imageMedia = reset($imageMedia);

        if (empty($imageMedia)) {
          // Create Media.
          $imageMedia = Media::create([
            'name' => $result->filename,
            'bundle' => 'image',
            'uid' => 1,
            'langcode' => $node['language'],
            'status' => 1,
            'field_media_image' => [
              'target_id' => $result->field_slide_image_fid,
              'alt' => $result->field_slide_image_alt,
              'title' => $result->field_slide_image_title,
            ],
          ]);
          $imageMedia->save();
        }

        // Populate fields.
        $paragraph->field_title->value = $result->title_field_value;
        $paragraph->field_description->value = $result->field_description_value;
        $paragraph->field_link = [
          'uri' => 'internal:/' . $result->field_link_url,
          'title' => $result->field_link_title,
        ];
        $paragraph->field_slide_image->target_id = $imageMedia->id();
        $paragraph->save();

        $paragraphs[] = $paragraph;
      }

      return $paragraphs;
    }
  }

}
