<?php

namespace Drupal\yukon_base;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Points the L2 child menu block's "Edit menu" link at the current page.
 */
class MenuContextualLinks implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * Adds the active link as the bigmenu "menu_link" query parameter.
   *
   * Runs after BlockViewBuilder::preRender(), which has already moved the
   * menu block's contextual links up to the block. The parameter has to be
   * set here, not in hook_contextual_links_alter(), because that runs in the
   * contextual render request, which knows nothing about the current page.
   */
  public static function preRender(array $build) {
    $link = \Drupal::service('menu.active_trail')->getActiveLink('main');
    if ($link && isset($build['#contextual_links']['menu'])) {
      // Extra route parameters become query parameters on the URL.
      $build['#contextual_links']['menu']['route_parameters']['menu_link'] = $link->getPluginId();
    }
    return $build;
  }

}
