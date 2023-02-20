<?php

namespace Drupal\media_directories_ui\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class RefreshDirectoryTree.
 */
class RefreshDirectoryTree implements CommandInterface {

  /**
   * The selected directory.
   *
   * @var int
   */
  protected $selectedDirectory;

  /**
   * Newly selected media entity ids.
   *
   * @var array
   */
  protected $newlySelectedEntityIds;

  /**
   * RefreshDirectoryTree constructor.
   *
   * @param int $selected_directory
   *   The selected directory.
   * @param array $newly_selected_entity_ids
   *   Newly selected media entity ids.
   */
  public function __construct($selected_directory = MEDIA_DIRECTORY_ROOT, array $newly_selected_entity_ids = []) {
    $this->selectedDirectory = $selected_directory;
    $this->newlySelectedEntityIds = $newly_selected_entity_ids;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'refreshDirectoryTree',
      'data' => [
        'selected_directory' => (isset($this->selectedDirectory) ? $this->selectedDirectory : MEDIA_DIRECTORY_ROOT),
        'newly_selected_entity_ids' => $this->newlySelectedEntityIds,
      ],
    ];
  }

}
