<?php

namespace Drupal\geophp;

/**
 * GeoPHPWrapper.
 */
class GeoPHPWrapper implements GeoPHPInterface {

  /**
   * Constructor.
   */
  public function __construct() {
    require_once \Drupal::service('extension.list.module')->getPath('geophp') . '/geoPHP/geoPHP.inc';
  }

  /**
   * {@inheritdoc}
   */
  public function version() {
    return \geoPHP::version();
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    return call_user_func_array(['\geoPHP', 'load'], func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapterMap() {
    return call_user_func_array(['\geoPHP', 'getAdapterMap'], func_get_args());
  }

}
