(function ($, Drupal, drupalSettings) {
  Drupal.MediaBrowser = {
    initialized: false,
    treeSelector: '#jstree-container',
    activeDirectory: -1,
    selectedMedia: [],
    targetBundles: [],
    searchString: null,
    cardinality: -1,
    remainingItems: 25,
    urls: {},
    keepSelectionOnChange: false
  };

  /**
   * Main init function.
   */
  Drupal.MediaBrowser.init = function () {
    if (this.initialized) {
      return;
    }

    // Init tree.
    Drupal.MediaBrowser.tree();

    if ('media_directories' in drupalSettings) {
      if ('target_bundles' in drupalSettings.media_directories) {
        this.targetBundles = drupalSettings.media_directories.target_bundles;
      }

      if ('cardinality' in drupalSettings.media_directories) {
        this.cardinality = drupalSettings.media_directories.cardinality;
      }

      if ('remaining' in drupalSettings.media_directories) {
        this.remainingItems = drupalSettings.media_directories.remaining;
      }
    }

    Drupal.MediaBrowser.toolbar.init();

    // Mark media browser as initialized.
    this.initialized = true;
  };

  /**
   * Get action url from settings.
   *
   * @param action_name
   * @returns {*}
   */
  Drupal.MediaBrowser.getUrl = function (action_name) {
    if ('media_directories' in drupalSettings) {
      if (action_name in drupalSettings.media_directories.url) {
        return drupalSettings.media_directories.url[action_name];
      }
    }
  };

  /**
   * Clear media from selection.
   * Some of the actions will change list of media items
   * so we might need to clear selected items.
   */
  Drupal.MediaBrowser.clearMediaSelection = function (resetSearch) {

    if (resetSearch == undefined) {
      resetSearch = true;
    }

    // Clear selection from global storage.
    Drupal.MediaBrowser.selectedMedia = [];
    // Reset also the search string.
    if (resetSearch) {
      Drupal.MediaBrowser.searchString = null;
      Drupal.MediaBrowser.toolbar.inputs.media_name_filter.val('');
    }
    // Notify toolbar of these changes.
    Drupal.MediaBrowser.toolbar.selectionChanged();

    // Remove the selected class from all items.
    $('.media-listing .media-item').removeClass('selected');
  };

  /**
   * Get elements which are selected.
   * State only stores media ID's, because DOM will change and
   * we need to restore selection in some cases.
   *
   * @returns {Array}
   */
  Drupal.MediaBrowser.getSelectedMids = function () {
    var mids = [];
    $.each(Drupal.MediaBrowser.selectedMedia, function (key, value) {
      mids.push(value);
    });
    return mids;
  };

  /**
   * Get DOM elements which are selected and visible.
   * State only stores media ID's, because DOM will change and
   * we need to restore selection in some cases.
   *
   * @returns {Array}
   */
  Drupal.MediaBrowser.getSelectedElements = function () {
    var elements = [];
    $.each(Drupal.MediaBrowser.getSelectedMids(), function (key, value) {
      var $media_element = Drupal.MediaBrowser.getMediaElement(value);

      if ($media_element.length > 0) {
        elements.push($media_element);
      }
    });
    return elements;
  };

  /**
   * Get a DOM element by a media entity id.
   *
   * @returns {Array}
   */
  Drupal.MediaBrowser.getMediaElement = function (mid) {
    return $('.browser--listing [data-mid="' + mid + '"]');
  };

  /**
   * Start blocking user gestures and show that something is in progress.
   */
  Drupal.MediaBrowser.startLoader = function () {
    var $browser = $('.browser');
    $browser.css('opacity', '0.5');
    $browser.css('pointer-events', 'none');
  };

  /**
   * Unlock the UI so user can start interacting again.
   */
  Drupal.MediaBrowser.stopLoader = function () {
    var $browser = $('.browser');
    $browser.css('opacity', '1');
    $browser.css('pointer-events', 'auto');
  };
})(jQuery, Drupal, drupalSettings);
