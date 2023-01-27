<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

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
 *   field_yukon_editorial_team:
 *     plugin: kellett_taxonomy
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "kellett_taxonomy",
 * )
 */
class Taxonomy extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $node = $row->getSource();
    $taxonomyField = !empty($node[$destination_property]) ? $node[$destination_property] : NULL;
    $vocabulary = $this->getVocabulary($destination_property);
    if (!empty($taxonomyField)) {
      if (!is_array($taxonomyField)) {
        $taxonomyField = explode(',', $taxonomyField);
      }
      $taxonomyData = [];

      foreach ($taxonomyField as $tid) {
        $taxonomyFieldTerm = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadByProperties([
            'tid' => $tid,
            'vid' => $vocabulary,
          ]);
        $taxonomyFieldTerm = reset( $taxonomyFieldTerm);

        if ($taxonomyFieldTerm) {
          $taxonomyData[] = ['target_id' => $taxonomyFieldTerm->id()];
        }
      }

      return $taxonomyData;
    }
  }

  /**
   * Get vocabulary.
   *
   * @param $field
   */
  protected function getVocabulary($field) {
    $vocabulary = '';

    if ($field === 'field_yukon_editorial_team') {
      $vocabulary = 'teams';
    }
    if ($field === 'field_department_term') {
      $vocabulary = 'department';
    }
    if ($field === 'field_blog_type') {
      $vocabulary = 'blog_type';
    }
    if ($field === 'field_category_term') {
      $vocabulary = 'category';
    }
    if ($field === 'field_subcategory') {
      $vocabulary = 'sub_category';
    }

    return $vocabulary;
  }

}
