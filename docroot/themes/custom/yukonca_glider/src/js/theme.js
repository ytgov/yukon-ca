/**
 * Isolate the context in an IIFE
 *
 * @param object $ jQuery object
 * @param object Drupal Drupal object
 * @param window window Current window object
 * @param document document Current document object
 */

(function ($, Drupal, once, window/* , document */) {
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

  Drupal.behaviors.toggleFilter = {
    /**
     * Search filters collapse logic.
     *
     * @param object context Dom element triggered this method
     *
     */
    attach (context/* , settings */) {
      const $filtersTitle = $('.region-sidebar-first .block-views-exposed-filter-blocksearch-page-1 div.title', context);
      const toggleClassName = 'collapsed';

      if ($filtersTitle.length > 0) {
        const $form = $('.region-sidebar-first .filters form', context);

        $(once('filtersClick', '.region-sidebar-first .block-views-exposed-filter-blocksearch-page-1 div.title', context)).click(() => {
          if ($(window).width() < 667) {
            $form.toggle('slow');
            $filtersTitle.toggleClass(toggleClassName);
          }
        });

        $(window).resize(() => {
          if (!$form.is(':visible') && $(window).width() > 667) {
            $form.show();
            $filtersTitle.removeClass(toggleClassName);
          }
          if ($form.is(':visible') && !$filtersTitle.hasClass(toggleClassName) && $(window).width() < 667) {
            $form.hide();
            $filtersTitle.addClass(toggleClassName);
          }
        });
      }
    },
  };
}(jQuery, Drupal, once, window, document));
