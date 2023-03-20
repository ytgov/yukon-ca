(function ($, Drupal, drupalSettings) {
  Drupal.AjaxCommands.prototype.refreshDirectoryTree = function (ajax, response, status) {
    var $jsTree = $(Drupal.MediaBrowser.treeSelector);
    var selected_directory = parseInt(response.data.selected_directory);

    if (response.data.newly_selected_entity_ids.length > 0) {
      if (drupalSettings.media_directories.selection_mode != 'keep') {
        // Clear selection from global storage.
        Drupal.MediaBrowser.clearMediaSelection();
      }
      // And persist storage for next jstree.change event, to keep the newly added items.
      Drupal.MediaBrowser.keepSelectionOnChange = true;
      $.each(response.data.newly_selected_entity_ids, function (key, value) {
        Drupal.MediaBrowser.selectedMedia.push(value);
      });
    }

    $jsTree.one('refresh.jstree', function () {
      var node_id = selected_directory === -1 ? 'dir-root' : 'dir-' + selected_directory;
      $(this).jstree(true).deselect_all();
      $(this).jstree(true).select_node(node_id);
      $(this).jstree(true).open_node(node_id);
    });

    $jsTree.jstree(true).refresh(false, true);
  };
})(jQuery, Drupal, drupalSettings);
