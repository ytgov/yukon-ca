/* global once, _paq */

(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.searchZeroResult = {
    attach (context) {
      if (typeof _paq === 'undefined') {
        return;
      }

      const settings = drupalSettings.yukonca_glider || {};
      const { searchQuery = '', resultCount } = settings;

      // Only manually track zero-result searches, since Matomo automatically
      // tracks searches with results.
      if (isNaN(parseInt(resultCount)) ||
          !isFinite(resultCount) ||
          parseInt(resultCount) !== 0) {
        return;
      }

      once('search-zero-result-matomo-track', 'body', context).forEach(() => {
        _paq.push(['trackSiteSearch', searchQuery, false, resultCount]);
      });
    },
  };
}(Drupal, drupalSettings, once));
