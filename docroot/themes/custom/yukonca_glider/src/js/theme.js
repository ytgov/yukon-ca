/**
 * Isolate the context in an IIFE
 *
 * @param object $ jQuery object
 * @param object Drupal Drupal object
 * @param window window Current window object
 * @param document document Current document object
 */

(function ($, Drupal, window/* , document */) {
  const $window = $(window);
  /**
   * Main script for the current theme
   */
  Drupal.behaviors.themeJS = {
    /**
     * Trigger this function when the pages and Drupal js core was loaded
     *
     * @param object context Dom element triggered this method
     * @param object settings Curent Drupal settings
     *
     */
    attach (context/* , settings */) {
      /* Show and hide header on scrolling. */
      const SCROLL_THRESHOLD = 200;
      const CLASS_SCROLLED = 'js-did-scroll';

      const $body = $('body', context);

      $window.on('scroll', () => {
        const offsetTop = $window.scrollTop();
        const didPassThreshold = offsetTop > SCROLL_THRESHOLD;

        if (didPassThreshold && !$body.hasClass(CLASS_SCROLLED)) {
          $body.addClass(CLASS_SCROLLED);
        } else if (!didPassThreshold && $body.hasClass(CLASS_SCROLLED)) {
          $body.removeClass(CLASS_SCROLLED);
        }
      });
    },
  };
}(jQuery, Drupal, window, document));
