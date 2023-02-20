(function ($, Drupal) {
  /**
   * Initialize Media Browser.
   *
   * @type {{attach: Drupal.behaviors.MediaDirectoriesUi.attach}}
   */
  Drupal.behaviors.MediaDirectoriesUi = {
    attach: function attach(context) {
      Drupal.MediaBrowser.init();
      Drupal.MediaBrowser.media.init();
    }
  };

  // Register global events after the one declared by jsTree dnd plugin.
  $(function() {
    Drupal.MediaBrowser.globalBindings();
  });

})(jQuery, Drupal);