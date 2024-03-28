(function ($, Drupal, drupalSettings) {
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

      
        
    }
  };
})(jQuery, Drupal, drupalSettings);