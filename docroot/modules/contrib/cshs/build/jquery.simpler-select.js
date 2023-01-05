/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};

;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js
function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/createClass.js
function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}
;// CONCATENATED MODULE: ./src/jquery.simpler-select.ts

/**
 * @file
 * Render standard select with hierarchical options: as set of selects, one for each level of the hierarchy.
 */




(function ($, wrapperClass, pluginName) {
  'use strict';

  var pluginId = 'plugin_' + pluginName;

  function $createElement(name) {
    return $(document.createElement(name));
  }

  var ClientSideHierarchicalSelect = /*#__PURE__*/function () {
    function ClientSideHierarchicalSelect(element, settings) {
      _classCallCheck(this, ClientSideHierarchicalSelect);

      this.options = [];
      this.groups = {};
      this.currentLevel = -1;
      this.$element = $(element);
      this.disabled = element.disabled;
      this.elementId = element.id;
      this.isMultiple = element.multiple;
      this.elementClasses = element.className.replace('simpler-select-root', 'simpler-select'); // Use `$.extend()` to not care about `Object.assign()` polyfill for IE11.

      this.settings = $.extend({
        noFirstLevelNone: false,
        noneLabel: '- Please choose -',
        noneValue: '_none',
        labels: []
      }, settings);
      this.init();
    }

    _createClass(ClientSideHierarchicalSelect, [{
      key: "init",
      value: function init() {
        // Ensure that we'll clearly initiate a new instance.
        this.destroy();
        this.buildOptions();
        var $currentWrapper = this.createSelectElement(this.$element, this.rootValue, !this.settings.noFirstLevelNone);

        if ($currentWrapper !== this.$element) {
          var lastSelectedValue = this.$element.val();

          if (lastSelectedValue instanceof Array) {
            lastSelectedValue = lastSelectedValue.pop();
          } else if (typeof lastSelectedValue === 'number') {
            lastSelectedValue = lastSelectedValue.toString();
          }

          if (lastSelectedValue) {
            // Reverse since we started from the last value.
            for (var _i2 = 0, _this$getLineage$reve2 = this.getLineage(lastSelectedValue).reverse(); _i2 < _this$getLineage$reve2.length; _i2++) {
              var value = _this$getLineage$reve2[_i2];
              $currentWrapper.find('select').val(value);
              $currentWrapper = this.createSelectElement($currentWrapper, value);
            }
          } // Hide the original.


          this.$element.hide();
        }
      }
    }, {
      key: "destroy",
      value: function destroy() {
        // Allow reinitialization here.
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        this['groups'] = {};
        this.options.length = 0;
        this.removeSubsequentSelects(this.$element.show());
      }
    }, {
      key: "buildOptions",
      value: function buildOptions() {
        var _a, _b;

        for (var _i4 = 0, _this$$element$0$opti2 = this.$element[0].options; _i4 < _this$$element$0$opti2.length; _i4++) {
          var option = _this$$element$0$opti2[_i4];
          var group = option.parentNode instanceof HTMLOptGroupElement ? option.parentNode.label : undefined;

          if (group) {
            // Create the mapping as we'll need the total count later.
            this.groups[group] = undefined;
          }

          this.options.push({
            group: group,
            value: option.value,
            label: option.text.trim(),
            // `undefined` and `''` results in an `undefined`.
            parent: option.dataset.parent || undefined
          });
        } // If the first option is a `_none`, then the next one is the
        // real where we should look for a parent to determine whether
        // the hierarchy starts from a specific item.


        this.rootValue = (_b = this.options[((_a = this.options[0]) === null || _a === void 0 ? void 0 : _a.value) === this.settings.noneValue ? 1 : 0]) === null || _b === void 0 ? void 0 : _b.parent;
      }
    }, {
      key: "createSelectElement",
      value: function createSelectElement($element, parent) {
        var _this = this;

        var addNoneOption = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
        var options = this.getChildren(parent);

        if (!options.length) {
          return $element;
        }

        this.currentLevel++;
        var levelPrefix = '--level-' + this.currentLevel;
        var selectId = this.elementId + levelPrefix;
        var $wrapper = $createElement('div') // Add level-specific class to ease the styling.
        .addClass([wrapperClass, wrapperClass + levelPrefix]) // Provide the read-only attribute for those who may need to query it.
        .attr('data-level', this.currentLevel);
        var $select = $createElement('select').addClass(this.elementClasses).attr('id', selectId) // Suppress `Argument of type 'boolean' is not assignable to
        // parameter of type 'string | number` because it's not true.
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore TS2345.
        .attr('disabled', this.disabled);

        if (addNoneOption) {
          $createElement('option').attr('value', this.settings.noneValue).attr('data-parent-value', parent || null).text(this.settings.noneLabel).appendTo($select);
        }

        for (var _i6 = 0; _i6 < options.length; _i6++) {
          var option = options[_i6];

          // Do not add the `_none` option 'cause it's already added above.
          if (option.value !== this.settings.noneValue) {
            this.createOptionElement($select, option);
          }
        }

        $select.on('change', function (event) {
          var _a;

          var selectedOption = event.target.options[event.target.selectedIndex];

          _this.removeSubsequentSelects($wrapper);

          _this.$element // The `data-parent-value` exists only on the `_none` option, meaning
          // we should refer to the selection picked in the previous select.
          .val(_this.getValue((_a = selectedOption.dataset.parentValue) !== null && _a !== void 0 ? _a : selectedOption.value)).trigger(event.type);

          if (selectedOption.value !== _this.settings.noneValue) {
            _this.createSelectElement($wrapper, selectedOption.value);
          }
        });

        if (this.settings.labels[this.currentLevel]) {
          $createElement('label').attr('for', selectId).text(this.settings.labels[this.currentLevel]).appendTo($wrapper);
        }

        this.$element.trigger(jQuery.Event("".concat(pluginName, "ChildCreated"), {
          $wrapper: $wrapper
        }));
        return $wrapper.append($select).insertAfter($element);
      }
    }, {
      key: "createOptionElement",
      value: function createOptionElement($select, option) {
        var _a;

        var _b, _c;

        var $option = $createElement('option').attr('value', option.value).text(option.label);

        if (this.getChildren(option.value).length) {
          $option.addClass('has-children');
        } // Group options on the first level only if there are at least 2 groups.


        if (!option.parent && option.group && Object.keys(this.groups).length > 1) {
          (_a = (_b = this.groups)[_c = option.group]) !== null && _a !== void 0 ? _a : _b[_c] = $createElement('optgroup').attr('label', option.group).appendTo($select); // The group is no longer `undefined`.
          // eslint-disable-next-line @typescript-eslint/ban-ts-comment
          // @ts-ignore

          $option.appendTo(this.groups[option.group]);
        } else {
          $option.appendTo($select);
        }

        return $option;
      }
      /**
       * Returns the value for a given `option` element.
       */

    }, {
      key: "getValue",
      value: function getValue(value) {
        return this.isMultiple ? this.getLineage(value) : value;
      }
      /**
       * Removes subsequent `select` elements after a given element.
       */

    }, {
      key: "removeSubsequentSelects",
      value: function removeSubsequentSelects($element) {
        var $wrappers = $element.nextAll('.' + wrapperClass);
        this.currentLevel -= $wrappers.length;

        if ($wrappers.length) {
          this.$element.trigger(jQuery.Event("".concat(pluginName, "ChildrenDeleted"), {
            $wrappers: $wrappers
          }));
        }

        $wrappers.remove();
      }
      /**
       * Returns the first found option with a given value.
       */

    }, {
      key: "getOptionByValue",
      value: function getOptionByValue(value) {
        for (var _i8 = 0, _this$options2 = this.options; _i8 < _this$options2.length; _i8++) {
          var option = _this$options2[_i8];

          if (option.value === value) {
            return option;
          }
        }

        return undefined;
      }
      /**
       * Returns the options that are children of a given parent.
       */

    }, {
      key: "getChildren",
      value: function getChildren(parent) {
        return this.options.filter(function (option) {
          return option.parent === parent;
        });
      }
      /**
       * Returns the values tree for a given value.
       */

    }, {
      key: "getLineage",
      value: function getLineage(value) {
        var parents = [value]; // eslint-disable-next-line no-constant-condition

        while (true) {
          if (value !== this.settings.noneValue) {
            var option = this.getOptionByValue(value); // Do not allow going beyond the configured root.

            if ((option === null || option === void 0 ? void 0 : option.parent) && option.parent !== this.rootValue) {
              parents.push(option.parent);
              var child = this.getOptionByValue(option.parent);

              if (child === null || child === void 0 ? void 0 : child.value) {
                value = child.value;
                continue;
              }
            }
          }

          break;
        }

        return parents;
      }
    }]);

    return ClientSideHierarchicalSelect;
  }();

  $.fn[pluginName] = function (settings) {
    return this.each(function (index, element) {
      if (!$.data(element, pluginId)) {
        if (element instanceof HTMLSelectElement) {
          $.data(element, pluginId, new ClientSideHierarchicalSelect(element, settings));
        } else {
          throw new Error("The \"".concat(element.id, "\" must be an instance of HTMLSelectElement."));
        }
      }
    });
  };
})(jQuery, 'select-wrapper', 'simplerSelect');
/******/ })()
;
//# sourceMappingURL=jquery.simpler-select.js.map