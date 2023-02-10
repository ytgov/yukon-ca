<?php

namespace Drupal\yukon_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\taxonomy\Plugin\migrate\source\d7\Term as D7Term;

/**
 * Customized source of Taxonomy migration to provide URL alias.
 *
 * @MigrateSource(
 *   id = "yukon_term"
 * )
 */
class Term extends D7Term {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $tid = $row->getSourceProperty('tid');
    $query = $this->select('url_alias', 'ua')
      ->fields('ua', ['alias']);
    $query->condition('ua.source', 'taxonomy/term/' . $tid);
    $alias = $query->execute()->fetchField();
    if (!empty($alias)) {
      $row->setSourceProperty('alias', '/' . $alias);
    }
    return parent::prepareRow($row);
  }

}
