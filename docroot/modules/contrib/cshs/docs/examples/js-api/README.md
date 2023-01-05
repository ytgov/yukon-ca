# JavaScript API

## React on hierarchy change

Use the below code as an example for building a custom logic when the new select items are created or existing deleted.

```javascript
(($) => {
  'use strict';

  Drupal.behaviors.myBehavior = {
    attach(context, settings) {
      $('select.simpler-select-root', context)
        .on('simplerSelectChildCreated', (event) => {
          // The `event.$wrapper` is an instance of jQuery<HTMLDivElement>.
          // The collection always contains only a single element, whose
          // children are `select` and, optionally, `label`.
        })
        .on('simplerSelectChildrenDeleted', (event) => {
          // The `event.$wrappers` is an instance of jQuery<HTMLDivElement>.
          // The collection may contain as many wrappers as many hierarchy
          // levels have been deleted. Each collection item's children are
          // `select` and, optionally, `label` elements.
        });
    },
  };
})(jQuery);
```
