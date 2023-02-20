(function ($) {
  /**
   * Image resize functionality.
   * Allow resetting values and keep aspect ratio.
   *
   * @type {{attach: Drupal.behaviors.MediaDirectoriesEditorImageResize.attach}}
   */
  Drupal.behaviors.MediaDirectoriesEditorImageResize = {
    attach: function attach() {
      var $container = $('.media-directories-editor--dimensions');
      var $reset = $('.media-directories-editor--reset', $container);
      var $width = $('.media-directories-editor--image-width', $container);
      var $height = $('.media-directories-editor--image-height', $container);
      var orig_width = $reset.data('width');
      var orig_height = $reset.data('height');

      $reset.on('click', function (e) {
        e.preventDefault();
        $width.val(orig_width);
        $height.val(orig_height);
      });

      $width.on('change', function () {
        var value = $(this).val();

        if (value && value !== '0') {
          value = Math.round(orig_height * (value / orig_width));
        }

        if (!isNaN(value)) {
          $height.val(value);
        }
      });

      $height.on('change', function () {
        var value = $(this).val();

        if (value && value !== '0') {
          value = Math.round(orig_width * (value / orig_height));
        }

        if (!isNaN(value)) {
          $width.val(value);
        }
      });
    }
  };
})(jQuery);