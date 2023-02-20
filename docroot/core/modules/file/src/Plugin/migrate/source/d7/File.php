<?php

namespace Drupal\file\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Drupal 7 file source from database.
 *
 * Available configuration keys:
 * - scheme: (optional) The scheme of the files to get from the source, for
 *   example, 'public' or 'private'. Can be a string or an array of schemes.
 *   The 'temporary' scheme is not supported. If omitted, all files in
 *   supported schemes are retrieved.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: d7_file
 *   scheme: public
 * @endcode
 *
 * In this example, public file values are retrieved from the source database.
 * For complete example, refer to the d7_file.yml migration.
 *
 * For additional configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 * @see d7_file.yml
 *
 * @MigrateSource(
 *   id = "d7_file",
 *   source_module = "file"
 * )
 */
class File extends FieldableEntity {

  /**
   * The public file directory path.
   *
   * @var string
   */
  protected $publicPath;

  /**
   * The private file directory path, if any.
   *
   * @var string
   */
  protected $privatePath;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('file_managed', 'f')
      ->fields('f')
      ->condition('f.uri', 'temporary://%', 'NOT LIKE')
      ->orderBy('f.timestamp');

    // Filter by scheme(s), if configured.
    if (isset($this->configuration['scheme'])) {
      $schemes = [];
      // Remove 'temporary' scheme.
      $valid_schemes = array_diff((array) $this->configuration['scheme'], ['temporary']);
      // Accept either a single scheme, or a list.
      foreach ((array) $valid_schemes as $scheme) {
        $schemes[] = rtrim($scheme) . '://';
      }
      $schemes = array_map([$this->getDatabase(), 'escapeLike'], $schemes);

      // Add conditions, uri LIKE 'public://%' OR uri LIKE 'private://%'.
      $conditions = $this->getDatabase()->condition('OR');
      foreach ($schemes as $scheme) {
        $conditions->condition('f.uri', $scheme . '%', 'LIKE');
      }
      $query->condition($conditions);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $this->publicPath = $this->variableGet('file_public_path', 'sites/default/files');
    $this->privatePath = $this->variableGet('file_private_path', NULL);
    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Compute the filepath property, which is a physical representation of
    // the URI relative to the Drupal root.
    $path = str_replace(['public:/', 'private:/'], [$this->publicPath, $this->privatePath], $row->getSourceProperty('uri'));
    // At this point, $path could be an absolute path or a relative path,
    // depending on how the scheme's variable was set. So we need to shear out
    // the source_base_path in order to make them all relative.
    $path = preg_replace('#' . preg_quote($this->configuration['constants']['source_base_path']) . '#', '', $path, 1);
    $row->setSourceProperty('filepath', $path);

    // File Entity module makes files as fieldable entities, so we can get
    // Field API field values.
    if ($this->moduleExists('file_entity')) {
      $type = $row->getSourceProperty('type');
      $fid = $row->getSourceProperty('fid');

      // If this entity was translated using Entity Translation, we need to get
      // its source language to get the field values in the right language.
      $entity_translatable = $this->isEntityTranslatable('file');
      $source_language = $this->getEntityTranslationSourceLanguage('file', $fid);
      $language = $entity_translatable && $source_language ? $source_language : $row->getSourceProperty('language');

      foreach ($this->getFields('file', $type) as $field_name => $field) {
        // Ensure we're using the right language if the entity and the field are
        // translatable.
        $field_language = $entity_translatable && $field['translatable'] ? $language : NULL;
        $row->setSourceProperty($field_name, $this->getFieldValues('file', $field_name, $fid, NULL, $field_language));
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fid' => $this->t('File ID'),
      'uid' => $this->t('The {users}.uid who added the file. If set to 0, this file was added by an anonymous user.'),
      'filename' => $this->t('File name'),
      'filepath' => $this->t('File path'),
      'filemime' => $this->t('File MIME Type'),
      'status' => $this->t('The published status of a file.'),
      'timestamp' => $this->t('The time that the file was added.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    $ids['fid']['alias'] = 'f';
    return $ids;
  }

}
