<?php

namespace Drupal\cshs\Plugin\views\filter;

use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;

/**
 * {@inheritdoc}
 *
 * @see \cshs_views_plugins_filter_alter()
 */
class CshsTaxonomyIndexTid extends TaxonomyIndexTid {

  use CshsTaxonomyIndex;

  public const ID = 'taxonomy_index_tid';

}
