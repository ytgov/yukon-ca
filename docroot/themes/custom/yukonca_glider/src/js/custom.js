/**
 * Isolate the context in an IIFE
 *
 * @param object $ jQuery object
 * @param object Drupal Drupal object
 * @param window window Current window object
 * @param document document Current document object
 */

(function ($, Drupal) {
  /**
   * Main script for the current theme
   */
  Drupal.behaviors.yukon = {
    attach: function (context, settings) {
      $('.yukon-accordion__expand').on('click', function(e) {
        e.preventDefault();
        $('.accordion .collapse').addClass('show');
        $('.panel-title a[data-toggle="collapse"]').find('.title-icon svg').addClass('fa-square-minus fa-square-plus');
      });
      $('.yukon-accordion__collapse').on('click', function(e) {
        e.preventDefault();
        $('.accordion .collapse').removeClass('show');
        $('.panel-title a[data-toggle="collapse"]').find('.title-icon svg').addClass('fa-square-plus fa-square-minus');
      });
      $('a.close_comment').css('display', 'none');
      $('a.add_new_comment').on('click', function(e) {
        e.preventDefault();
        $(this).next().css('display', 'block');
        $('a.close_comment').css('display', 'block');
        $(this).css('display', 'none');
      });
      $('a.close_comment').on('click', function(e) {
        e.preventDefault();
        $(this).prev().css('display', 'none');
        $('a.add_new_comment').css('display', 'block');
        $(this).css('display', 'none');
      });
    },
  };
})(jQuery, Drupal);
