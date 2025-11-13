/**
 * Isolate the context in an IIFE
 *
 * @param object $ jQuery object
 * @param object Drupal Drupal object
 * @param window window Current window object
 * @param document document Current document object
 */

(function ($, Drupal, once) {
  /**
   * Main script for the current theme
   */
  Drupal.behaviors.yukon = {
    attach () {
      jQuery('select#edit-event-year-filter').change(function () {
        const year = jQuery(this).val();
        const url = `/docroot/year_month_change/${year}`;

        jQuery.ajax({
          type: 'POST',
          url,
          data: { year },
        }).done((data) => {
          if (data[0]) {
            const $country = jQuery('select#edit-event-month-filter');
            $country.empty();

            const keys = Object.keys(data[0]);

            $country.append('<option value="All">- Any -</option>');
            for (let i = 0; i < keys.length; i++) {
              const key = keys[i];
              $country.append(`<option value="${keys[i]}">${data[0][key]}</option>`);
            }
          }
        });
      });

      $('.yukon-accordion__expand').click((e) => {
        e.preventDefault();
        $('.accordion .collapse').addClass('show');
        $('.panel-title a[data-toggle="collapse"]').find('.title-icon svg').addClass('fa-square-minus fa-square-plus');
      });

      $('.yukon-accordion__collapse').click((e) => {
        e.preventDefault();
        $('.accordion .collapse').removeClass('show');
        $('.panel-title a[data-toggle="collapse"]').find('.title-icon svg').addClass('fa-square-plus fa-square-minus');
      });

      $('a.add_new_comment').click((e) => {
        e.preventDefault();
        $('.comment-blog-comments-form ').show();
        $('a.close_comment').show();
        $('a.add_new_comment').hide();
      });

      $('a.close_comment').click((e) => {
        e.preventDefault();
        $('.comment-blog-comments-form ').hide();
        $('a.close_comment').hide();
        $('a.add_new_comment').show();
      });

      $('.page-node-type-department .accordion .accordion-item:first-child .accordion-collapse').addClass('show');

      jQuery(document).ready(() => {
        if (!$('#block-yukonca-glider-views-block-related-tasks-block-1 .views-field-field-related-tasks .field-content div').hasClass('item-list')) {
          $('#block-yukonca-glider-views-block-related-tasks-block-1').hide();
        }

        function filterOpener () {
          const ele = $('aside .filters h2.title');

          if ($(window).width() < 768) {
            $(ele).click(function () {
              $(this).toggleClass('toggled');
              $('aside .filters form').stop(!0).slideToggle();
            });
          } else {
            $(ele).removeClass('toggled');
            $('aside .filters form').show();
          }
        }

        filterOpener();

        $(window).resize(() => {
          filterOpener();
        });

        const $sidebar = jQuery('.layout-sidebar-first, .layout-sidebar-second');

        if ($sidebar.length) {
          const sidebarOffsetTop = $sidebar.offset().top;

          jQuery(window).on('scroll resize', () => {
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
        }

        $('[data-once="bef-auto-submit"] .js-form-item .js-form-item [type="checkbox"]').change(() => {
          $('html, body').animate({
            scrollTop: $('#block-views-block-find-a-campground, #block-views-block-block-backcountry-campgrounds').offset().top,
          }, 1000);
        });

        const tables = document.querySelectorAll('table:not(.table-wrapper > table)');

        tables.forEach((table) => {
          const wrapper = document.createElement('div');
          wrapper.className = 'table-wrapper';
          table.parentNode.insertBefore(wrapper, table);
          wrapper.appendChild(table);
        });
      });
    },
  };

  Drupal.behaviors.quickExit = {
    attach (context) {
      once('jsBehaviour', '.quick-exit-btn', context).forEach((element) => {
        element.addEventListener('click', (event) => {
          event.preventDefault();

          const newUrl = element.getAttribute('href');
          const previousUrl = drupalSettings.quick_exit.previous;
          const backUrl = drupalSettings.quick_exit.back;

          window.history.replaceState({}, '', backUrl);

          window.open(newUrl, '_blank');
          window.location.href = previousUrl;
        });
      });
    },
  };

  Drupal.behaviors.clickToCall = {
    attach (context) {
      once('clickToCall', '.click-to-call-button', context).forEach((button) => {
        button.addEventListener('click', (event) => {
          event.preventDefault();
          const container = button.closest('.click-to-call-floating-button');
          const telephone = container ? container.dataset.telephone : null;

          if (telephone) {
            // Open telephone app with the number.
            window.location.href = `tel:${telephone}`;
          }
        });
      });
    },
  };
}(jQuery, Drupal, window.once));
