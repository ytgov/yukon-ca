/**
 * @file
 * Behavior which initializes the simplerSelect jQuery Plugin.
 */

import './css/cshs.scss';

(($): void => {
  'use strict';

  Drupal.behaviors.cshs = {
    attach(context, settings): void {
      $<HTMLSelectElement>('select.simpler-select-root', context)
        .once('cshs')
        .each((index, element) => {
          if (settings?.cshs[element.id]) {
            $(element).simplerSelect(settings.cshs[element.id]);
          }
        });
    },
  };
})(jQuery);
