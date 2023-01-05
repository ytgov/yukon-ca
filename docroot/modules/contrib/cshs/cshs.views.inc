<?php

/**
 * @file
 * Views integration.
 */

use Drupal\cshs\Plugin\views\filter\CshsTaxonomyIndexTid;
use Drupal\cshs\Plugin\views\filter\CshsTaxonomyIndexTidDepth;

/**
 * Implements hook_views_plugins_filter_alter().
 *
 * @internal
 */
function cshs_views_plugins_filter_alter(array &$plugins): void {
  $plugins[CshsTaxonomyIndexTid::ID]['class'] = CshsTaxonomyIndexTid::class;
  $plugins[CshsTaxonomyIndexTidDepth::ID]['class'] = CshsTaxonomyIndexTidDepth::class;
}
