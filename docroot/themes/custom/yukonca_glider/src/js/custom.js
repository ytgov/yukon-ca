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
      jQuery('select#edit-event-year-filter').change(function () {
            var year = jQuery(this).val();
            url = "/docroot/year_month_change/" + year;

            jQuery.ajax({
                type: "POST",
                url: url,
                data: {'year': year}

            }).done(function (data) {
                  if(data[0]) {
                      var $country = jQuery('select#edit-event-month-filter');
                      $country.empty();
    
                      const keys = Object.keys(data[0]);
                      $country.append('<option value="All">- Any -</option>');
                      for (let i = 0; i < keys.length; i++) {
                        
                        const key = keys[i];
                        $country.append('<option value="' + keys[i] + '">' + data[0][key] + '</option>');
                        
                      }
                  }
                  
                  
                  
            });
        });

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

      $('.page-node-type-department .accordion .accordion-item:first-child .accordion-collapse').addClass('show');
      
      $(window).on('resize', function() {
        filterOpener();
      });
      
      jQuery(document).ready(function() {
          function filterOpener() {
            let ele = $('aside .filters h2.title');
            
            if($(window).width() < 768) {
                $(ele).on('click', function() {
                    $(this).toggleClass('toggled');
                    $('aside .filters form').stop(!0).slideToggle();
                });
            } else {
                $(ele).removeClass('toggled');
                $('aside .filters form').show();
            }
          }
          
          filterOpener();
          
          var $sidebar = jQuery('.layout-sidebar-second');
          var sidebarOffsetTop = $sidebar.offset().top;

          jQuery(window).on('scroll resize', function() {
            if (jQuery(window).width() <= 768) {
              if (jQuery(window).scrollTop() >= sidebarOffsetTop) {
                $sidebar.addClass('sticky-active');
              } else {
                $sidebar.removeClass('sticky-active');
              }
            } else {
              $sidebar.removeClass('sticky-active');
              filterOpener();
            }
          });
      });
    },
  };
})(jQuery, Drupal);
