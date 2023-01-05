/**
 * @file
 * Render standard select with hierarchical options: as set of selects, one for each level of the hierarchy.
 */

(($, wrapperClass, pluginName): void => {
  'use strict';

  const pluginId = 'plugin_' + pluginName;

  function $createElement<K extends keyof HTMLElementTagNameMap>(name: K): JQuery<HTMLElementTagNameMap[K]> {
    return $<HTMLElementTagNameMap[K]>(document.createElement(name));
  }

  class ClientSideHierarchicalSelect implements Cshs.Plugin {
    protected readonly $element: JQuery<HTMLSelectElement>;
    protected readonly settings: Cshs.Settings;
    protected readonly disabled: boolean;
    protected readonly elementId: string;
    protected readonly isMultiple: boolean;
    protected readonly elementClasses: string;
    protected readonly options: Cshs.Option[] = [];
    protected readonly groups: Record<string, JQuery<HTMLOptGroupElement> | undefined> = {};
    protected currentLevel = -1;
    protected rootValue: string | undefined;

    constructor(element: HTMLSelectElement, settings: Partial<Cshs.Settings>) {
      this.$element = $(element);
      this.disabled = element.disabled;
      this.elementId = element.id;
      this.isMultiple = element.multiple;
      this.elementClasses = element.className.replace('simpler-select-root', 'simpler-select');
      // Use `$.extend()` to not care about `Object.assign()` polyfill for IE11.
      this.settings = $.extend({
        noFirstLevelNone: false,
        noneLabel: '- Please choose -',
        noneValue: '_none',
        labels: [],
      }, settings);

      this.init();
    }

    init(): void {
      // Ensure that we'll clearly initiate a new instance.
      this.destroy();
      this.buildOptions();

      let $currentWrapper = this.createSelectElement(this.$element, this.rootValue, !this.settings.noFirstLevelNone);

      if ($currentWrapper !== this.$element) {
        let lastSelectedValue = this.$element.val();

        if (lastSelectedValue instanceof Array) {
          lastSelectedValue = lastSelectedValue.pop();
        } else if (typeof lastSelectedValue === 'number') {
          lastSelectedValue = lastSelectedValue.toString();
        }

        if (lastSelectedValue) {
          // Reverse since we started from the last value.
          for (const value of this.getLineage(lastSelectedValue).reverse()) {
            $currentWrapper.find('select').val(value);
            $currentWrapper = this.createSelectElement($currentWrapper, value);
          }
        }

        // Hide the original.
        this.$element.hide();
      }
    }

    destroy(): void {
      // Allow reinitialization here.
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      this['groups'] = {};
      this.options.length = 0;
      this.removeSubsequentSelects(this.$element.show());
    }

    protected buildOptions(): void {
      for (const option of this.$element[0].options) {
        const group = option.parentNode instanceof HTMLOptGroupElement ? option.parentNode.label : undefined;

        if (group) {
          // Create the mapping as we'll need the total count later.
          this.groups[group] = undefined;
        }

        this.options.push({
          group,
          value: option.value,
          label: option.text.trim(),
          // `undefined` and `''` results in an `undefined`.
          parent: option.dataset.parent || undefined,
        });
      }

      // If the first option is a `_none`, then the next one is the
      // real where we should look for a parent to determine whether
      // the hierarchy starts from a specific item.
      this.rootValue = this.options[this.options[0]?.value === this.settings.noneValue ? 1 : 0]?.parent;
    }

    protected createSelectElement<T extends JQuery>($element: T, parent?: string, addNoneOption = true): T | JQuery<HTMLDivElement> {
      const options = this.getChildren(parent);

      if (!options.length) {
        return $element;
      }

      this.currentLevel++;

      const levelPrefix = '--level-' + this.currentLevel;
      const selectId = this.elementId + levelPrefix;
      const $wrapper = $createElement('div')
        // Add level-specific class to ease the styling.
        .addClass([wrapperClass, wrapperClass + levelPrefix])
        // Provide the read-only attribute for those who may need to query it.
        .attr('data-level', this.currentLevel);
      const $select = $createElement('select')
        .addClass(this.elementClasses)
        .attr('id', selectId)
        // Suppress `Argument of type 'boolean' is not assignable to
        // parameter of type 'string | number` because it's not true.
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore TS2345.
        .attr('disabled', this.disabled);

      if (addNoneOption) {
        $createElement('option')
          .attr('value', this.settings.noneValue)
          .attr('data-parent-value', parent || null)
          .text(this.settings.noneLabel)
          .appendTo($select);
      }

      for (const option of options) {
        // Do not add the `_none` option 'cause it's already added above.
        if (option.value !== this.settings.noneValue) {
          this.createOptionElement($select, option);
        }
      }

      $select.on('change', (event): void => {
        const selectedOption = event.target.options[event.target.selectedIndex];

        this.removeSubsequentSelects($wrapper);

        this
          .$element
          // The `data-parent-value` exists only on the `_none` option, meaning
          // we should refer to the selection picked in the previous select.
          .val(this.getValue(selectedOption.dataset.parentValue ?? selectedOption.value))
          .trigger(event.type);

        if (selectedOption.value !== this.settings.noneValue) {
          this.createSelectElement($wrapper, selectedOption.value);
        }
      });

      if (this.settings.labels[this.currentLevel]) {
        $createElement('label')
          .attr('for', selectId)
          .text(this.settings.labels[this.currentLevel])
          .appendTo($wrapper);
      }

      this.$element.trigger(jQuery.Event(`${pluginName}ChildCreated`, { $wrapper }));

      return $wrapper
        .append($select)
        .insertAfter($element);
    }

    protected createOptionElement($select: JQuery<HTMLSelectElement>, option: Cshs.Option): JQuery<HTMLOptionElement> {
      const $option = $createElement('option')
        .attr('value', option.value)
        .text(option.label);

      if (this.getChildren(option.value).length) {
        $option.addClass('has-children');
      }

      // Group options on the first level only if there are at least 2 groups.
      if (!option.parent && option.group && Object.keys(this.groups).length > 1) {
        this.groups[option.group] ??= $createElement('optgroup')
          .attr('label', option.group)
          .appendTo($select);

        // The group is no longer `undefined`.
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
    protected getValue(value: string): string | string[] {
      return this.isMultiple ? this.getLineage(value) : value;
    }

    /**
     * Removes subsequent `select` elements after a given element.
     */
    protected removeSubsequentSelects($element: JQuery): void {
      const $wrappers = $element.nextAll('.' + wrapperClass);
      this.currentLevel -= $wrappers.length;

      if ($wrappers.length) {
        this.$element.trigger(jQuery.Event(`${pluginName}ChildrenDeleted`, { $wrappers }));
      }

      $wrappers.remove();
    }

    /**
     * Returns the first found option with a given value.
     */
    protected getOptionByValue(value: string): Cshs.Option | undefined {
      for (const option of this.options) {
        if (option.value === value) {
          return option;
        }
      }

      return undefined;
    }

    /**
     * Returns the options that are children of a given parent.
     */
    protected getChildren(parent: string | undefined): Cshs.Option[] {
      return this.options.filter((option) => option.parent === parent);
    }

    /**
     * Returns the values tree for a given value.
     */
    protected getLineage(value: string): string[] {
      const parents: string[] = [value];

      // eslint-disable-next-line no-constant-condition
      while (true) {
        if (value !== this.settings.noneValue) {
          const option = this.getOptionByValue(value);

          // Do not allow going beyond the configured root.
          if (option?.parent && option.parent !== this.rootValue) {
            parents.push(option.parent);

            const child = this.getOptionByValue(option.parent);

            if (child?.value) {
              value = child.value;
              continue;
            }
          }
        }

        break;
      }

      return parents;
    }
  }

  $.fn[pluginName] = function (settings: Partial<Cshs.Settings>) {
    return this.each((index, element) => {
      if (!$.data(element, pluginId)) {
        if (element instanceof HTMLSelectElement) {
          $.data(element, pluginId, new ClientSideHierarchicalSelect(element, settings));
        } else {
          throw new Error(`The "${element.id}" must be an instance of HTMLSelectElement.`);
        }
      }
    });
  };
})(jQuery, 'select-wrapper', 'simplerSelect');
