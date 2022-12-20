/**
 * Isolate the context in an IIFE
 *
 * @param object $ jQuery object
 * @param object Drupal Drupal object
 * @param window window Current window object
 */

(function ($, Drupal, window) {
  /**
   * Main script for the current theme
   */
  Drupal.behaviors.timeline = {
    attach (context) {
      // Starting from here, only act on initial page load.
      if (context !== document) {
        return;
      }
      const $window           = $(window);
      function createTimelineSwiper () {
        const TIMELINE_PREFIX             = 'timeline';
        const TIMELINE_CLASS              = `.${TIMELINE_PREFIX}`;
        const $timeline                   = $(TIMELINE_CLASS, context);

        $timeline.each(function () {
          const timelineContainerWidth  = $(this).find('.container').width();
          const windowWidth             = $window.width();
          const paddingSize             = (windowWidth - timelineContainerWidth) / 2;
          const $imageScroll            = $(this).find('.image-scroll');
          const $timelineSwiper         = $(this).find('.timeline-swiper');
          const $leftButton             = $(this).find('.timeline-prev');
          const $rightButton            = $(this).find('.timeline-next');
          const scrolledImage           = $(this).find('.image-scroll img');

          $imageScroll.css('padding-left', paddingSize);
          $imageScroll.css('padding-right', paddingSize);

          scrolledImage.attr('draggable', false);

          function sideScroll (element, direction, speed, distance, step) {
            let scrollAmount = 0;
            const slideTimer = setInterval(() => {
              if (direction === 'left') {
                element.scrollLeft -= step;
              } else {
                element.scrollLeft += step;
              }
              scrollAmount += step;
              if (scrollAmount >= distance) {
                window.clearInterval(slideTimer);
              }
            }, speed);
          }

          $leftButton.click((e) => {
            e.preventDefault();
            sideScroll((document.querySelector('.timeline-swiper')), 'left', 5, 350, 5);
          });

          $rightButton.click((e) => {
            e.preventDefault();
            sideScroll((document.querySelector('.timeline-swiper')), 'right', 5, 350, 5);
          });

          $timelineSwiper.on('scroll', function () {
            const cur = $(this).scrollLeft();
            if (cur === 0) {
              $(this).siblings('.gradient-left').addClass('hidden');
              $(this).siblings('.gradient-left').addClass('hidden');
              $leftButton.addClass('button-disabled');
            } else {
              const max = $(this)[0].scrollWidth - $(this).parent().width() - 5;
              if (cur >= max) {
                $leftButton.removeClass('button-disabled');
                $rightButton.addClass('button-disabled');
                $(this).siblings('.gradient-right').addClass('hidden');
                $(this).siblings('.gradient-left').removeClass('hidden');
              } else {
                $leftButton.removeClass('button-disabled');
                $rightButton.removeClass('button-disabled');
                $(this).siblings('.gradient-right').removeClass('hidden');
                $(this).siblings('.gradient-left').removeClass('hidden');
              }
            }
          });
          $(this).find('.timeline-swiper').trigger('scroll');
        });

        const slider = document.querySelector('.timeline-swiper');
        let isDown = false;
        let startX;
        let scrollLeft;

        slider.addEventListener('mousedown', (e) => {
          isDown = true;
          slider.classList.add('active');
          startX = e.pageX - slider.offsetLeft;
          scrollLeft = slider.scrollLeft;
        });
        slider.addEventListener('mouseleave', () => {
          isDown = false;
          slider.classList.remove('active');
        });
        slider.addEventListener('mouseup', () => {
          isDown = false;
          slider.classList.remove('active');
        });
        slider.addEventListener('mousemove', (e) => {
          if (!isDown) return;
          e.preventDefault();
          const x = e.pageX - slider.offsetLeft;
          const walk = (x - startX) * 1.5; // scroll-fast
          slider.scrollLeft = scrollLeft - walk;
        });
      }
      createTimelineSwiper();

      $window.resize(() => {
        createTimelineSwiper();
      });
    },
  };
}(jQuery, Drupal, window));
