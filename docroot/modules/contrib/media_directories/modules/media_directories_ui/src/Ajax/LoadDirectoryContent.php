<?php

namespace Drupal\media_directories_ui\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class LoadDirectoryContent.
 */
class LoadDirectoryContent implements CommandInterface {

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'loadDirectoryContent',
    ];
  }

}
