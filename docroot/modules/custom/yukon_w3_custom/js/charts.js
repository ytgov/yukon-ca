(function ($, Drupal, drupalSettings, once) {
  /**
   * @namespace
   */
  Drupal.behaviors.yukon_w3_custom = {
    attach: function (context) {
      var data = drupalSettings.chart_data;
        for (const [key, value] of Object.entries(data)) {
          let options = JSON.parse(value);

            options.accessibility = {
              linkedDescription: "#chart-graph-" + key
            };
            
            options.credits = {
              enabled: false
            };
            
            Highcharts.chart('chart-graph-' + key, options);
        }

      
        $(once('expand-all', '.yukon-accordion__expand', context)).each(function () {
            $(this).on('click', function(e) {
                e.preventDefault();
                $('.accordion .collapse').addClass('show');
                $('.panel-title a[data-toggle="collapse"]').find('.title-icon svg').addClass('fa-square-minus fa-square-plus');
            });
        });
        
        $(once('collapse-all', '.yukon-accordion__collapse', context)).each(function () {
            $(this).on('click', function(e) {
                e.preventDefault();
                $('.accordion .collapse').removeClass('show');
                $('.panel-title a[data-toggle="collapse"]').find('.title-icon svg').addClass('fa-square-plus fa-square-minus');
            });
        });
        
        $(once('collapse-all', '.panel-title a', context)).each(function () {
            $(this).on('click', function(e) {
                e.preventDefault();
                $($(this).attr('href')).toggleClass('show');
                $(this).find('.title-icon svg').toggleClass('fa-square-minus fa-square-plus');
            });
        });
    }
  };
})(jQuery, Drupal, drupalSettings, once);
