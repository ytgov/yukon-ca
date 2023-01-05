<?php

namespace Drupal\cshs\Plugin\views\filter;

use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTidDepth;

/**
 * {@inheritdoc}
 *
 * @see \cshs_views_plugins_filter_alter()
 */
class CshsTaxonomyIndexTidDepth extends TaxonomyIndexTidDepth {

  use CshsTaxonomyIndex;

  public const ID = 'taxonomy_index_tid_depth';

}
