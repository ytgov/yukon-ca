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
  Drupal.behaviors.carouselJS = {
    attach (context) {
      const carousel = $('.carousel.has-grey-background', context);

      const checkNextItem = () => {
        carousel.each(function () {
          const thisItem  = $(this);
          const nextItem  = $(this).next();

          if (thisItem.next('.post-card--large').length) {
            thisItem.css('margin-bottom', '0');
            nextItem.css('margin-top', '0');
          }
        });
      };
      checkNextItem();
    },
  };
}(jQuery, Drupal));
