<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * Adds taxonomy terms to node content.
 *
 * Example:
 *
 * @code
 * process:
 *   field_yukon_editorial_team:
 *     plugin: yg_taxonomy
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "yg_taxonomy",
 * )
 */
class Taxonomy extends YGMigratePluginBase {

  /**
   * Taxonomy term with translation.
   *
   * @var array
   *   The taxonomy term with translations.
   */
  protected $taxonomyTermTranslation;

  /**
   * Taxonomy term without translation.
   *
   * @var array
   *   The taxonomy term without translations.
   */
  protected $taxonomyTerm;

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

      foreach ($taxonomyField as $id => $tid) {
        $this->taxonomyTermTranslation = $this->getTaxonomyTerm($tid);
        $term = $this->taxonomyTermTranslation;
        if (empty($this->taxonomyTermTranslation)) {
          $this->taxonomyTerm = $this->getTaxonomyTerm($tid, FALSE);
          $term = $this->taxonomyTerm;
        }
        $taxonomyFieldTerm = $this->entityTypeManager
          ->getStorage('taxonomy_term')
          ->loadByProperties([
            'name' => $term->name,
            'vid' => $vocabulary,
          ]);
        $taxonomyFieldTerm = reset($taxonomyFieldTerm);

        if (!empty($taxonomyFieldTerm)) {
          $translatedLanguage = 'fr';
          if (!$taxonomyFieldTerm->hasTranslation($translatedLanguage)) {
            if ($this->taxonomyTermTranslation) {
              $taxonomyFieldTerm->addTranslation($translatedLanguage, [
                'name' => $this->taxonomyTermTranslation->translation,
              ]);
            }
            $taxonomyFieldTerm->save();
          }
          $taxonomyData[$id] = ['target_id' => $taxonomyFieldTerm->id()];
        }
        else {
          if (!empty($this->taxonomyTermTranslation)) {
            if (!empty($this->taxonomyTermTranslation->name)) {
              $taxonomyTerm = Term::create([
                'vid' => $vocabulary,
                'name' => $this->taxonomyTermTranslation->name,
                'description' => $this->taxonomyTermTranslation->description,
                'weight' => $this->taxonomyTermTranslation->weight,
                'langcode' => 'en',
              ]);
              $taxonomyTerm->save();
              if (!$taxonomyTerm->hasTranslation('fr')) {
                $taxonomyTerm->addTranslation('fr', [
                  'name' => $this->taxonomyTermTranslation->translation,
                ]);
                $taxonomyTerm->save();
              }
            }
          }
          else {
            if (!empty($this->taxonomyTerm->name)) {
              $taxonomyTerm = Term::create([
                'vid' => $vocabulary,
                'name' => $this->taxonomyTerm->name,
                'description' => $this->taxonomyTerm->description,
                'weight' => $this->taxonomyTerm->weight,
                'langcode' => 'en',
              ]);
              $taxonomyTerm->save();
            }
          }

          $taxonomyData[$id] = ['target_id' => $taxonomyTerm->id()];
        }
      }

      return $taxonomyData;
    }
  }

  /**
   * Get vocabulary.
   *
   * @param string $field
   *   The field to get vocabulary for.
   */
  protected function getVocabulary(string $field): string {
    $vocabulary = '';

    // General.
    if ($field === 'field_yukon_editorial_team') {
      $vocabulary = 'teams';
    }
    if ($field === 'field_department_term') {
      $vocabulary = 'department';
    }

    // Blog.
    if ($field === 'field_blog_type') {
      $vocabulary = 'blog_type';
    }
    if ($field === 'field_category_term') {
      $vocabulary = 'category';
    }
    if ($field === 'field_subcategory') {
      $vocabulary = 'sub_category';
    }

    // Event.
    if ($field === 'field_community') {
      $vocabulary = 'community';
    }

    return $vocabulary;
  }

  /**
   * Get information about the requested term.
   *
   * @param array $tid
   *   The term id.
   * @param bool $translationCheck
   *   Whether to request translations.
   *
   * @return array|object
   *   Return all tid data.
   */
  protected function getTaxonomyTerm(array $tid, bool $translationCheck = TRUE) {
    $connection = Database::getConnection('default', 'migrate');
    $query = $connection->select('taxonomy_term_data', 'ttd')
      ->fields('ttd', [
        'tid',
        'name',
        'description',
        'weight',
      ]);
    if ($translationCheck) {
      $query->innerJoin('i18n_string', 'i18n', 'i18n.objectid = ttd.tid');
      $query->fields('i18n',
        [
          'lid',
          'type',
        ]);
      $query->innerJoin('locales_target', 'lt', 'lt.lid = i18n.lid');
      $query->fields('lt',
        [
          'translation',
          'language',
        ]);
      $query->condition('i18n.type', 'term');
    }
    $query->condition('ttd.tid', $tid);

    return $query->execute()->fetchObject();
  }

}
