/**
 * Isolate the context in an IIFE
 *
 * @param object $ jQuery object
 * @param object Drupal Drupal object
 * @param window window Current window object
 */

(function ($, Drupal) {
  /**
   * Main script for the current theme
   */
  Drupal.behaviors.tabsJS = {
    attach (context) {
      const tabContainer = $('.tabs', context);
      const winLocation  = window.location;

      tabContainer.each(() => {
        /* Navigate to tab if it exists in the URL. */
        if (winLocation.hash.substr(0, 2) === '#!') {
          $(`a[data-toggle="tab"][href="#${winLocation.hash.substr(2)}"]`).tab('show');
        }

        $('a[data-toggle="tab"]', context).on('shown.bs.tab', (e) => {
          // On tab switch: update URL fragment.
          const hash = $(e.target).attr('href');
          if (hash.substr(0, 1) === '#') {
            winLocation.replace(`#!${hash.substr(1)}`);
          }
          const tabScrollPosition = 'center';
          const $activeTab = document.querySelector(`${hash}-tab`);
          $activeTab.scrollIntoView({ behavior: 'smooth', block: tabScrollPosition });
        });
      });
    },
  };
}(jQuery, Drupal));
