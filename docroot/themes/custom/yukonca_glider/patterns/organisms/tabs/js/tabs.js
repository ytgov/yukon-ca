/**
 * Isolate the context in an IIFE
 *
 * @param object $ jQuery object
 * @param object Drupal Drupal object
 * @param window window Current window object
 */

(function ($, Drupal, once) {
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

      $(once('arrows', '.pills-arrow-buttons .btn-link', context)).click(function () {
        const activeTab = $('.nav-pills a.nav-link.active');
        if ($(this).hasClass('next-step')) {
          $(this).prev().children('.pills-btn-link').show();
          activeTab.parent().next().children('a').addClass('active');
          activeTab.removeClass('active');

          if ($('.nav-pills .nav-item:last-child a').hasClass('active')) {
            $(this).hide();
          }
        } else if ($(this).hasClass('prev-step')) {
          $(this).next().show();
          activeTab.parent().prev().children('a').addClass('active');
          activeTab.removeClass('active');
          if ($('.nav-pills .nav-item:first-child a').hasClass('active')) {
            $(this).children('.pills-btn-link').hide();
          }
        }
      });

      $('.nav-pills .nav-item').click(function (e) {
        $('.pills-arrow-buttons .btn-link .pills-btn-link').show();

        if ($('.nav-pills .nav-item:first-child a').hasClass('active')) {
          $('.pills-arrow-buttons .prev-step.btn-link .pills-btn-link').hide();
        }
        if ($('.nav-pills .nav-item:last-child a').hasClass('active')) {
          $('.pills-arrow-buttons .next-step.btn-link .pills-btn-link').hide();
        }
      });
    },
  };
}(jQuery, Drupal, once));
