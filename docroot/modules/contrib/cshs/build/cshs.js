/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};
/**
 * @file
 * Behavior which initializes the simplerSelect jQuery Plugin.
 */


(function ($) {
  'use strict';

  Drupal.behaviors.cshs = {
    attach: function attach(context, settings) {
      $('select.simpler-select-root', context).once('cshs').each(function (index, element) {
        if (settings === null || settings === void 0 ? void 0 : settings.cshs[element.id]) {
          $(element).simplerSelect(settings.cshs[element.id]);
        }
      });
    }
  };
})(jQuery);
/******/ })()
;
//# sourceMappingURL=cshs.js.map