# JS Conventions

## Files

You should include your js files into the `src` folder in order to create the transpiled and minified verions on `dist` folder. Also, you should include into your libraries the transpiled files intead the source code files.

## Adding a Javascript file in Drupal

We have left out any default JavaScript files in Cog to eliminate any extraneous code when installing. In the code below, we will add the library reference and provide an example Javascript file (containing local closures around Drupal.behaviors) as a reference to add your first JS file to your them.

### Filename

`example.libraries.yml`

### File contents

```yaml
lib:
  version: VERSION
  js:
    js/dist/theme.min.js: { minified: true }
  dependencies:
    - core/jquery
    - core/drupal
```

### Filename

`js/theme.js`

### File contents

```js
(function ($, Drupal, window, document) {
  'use strict';

  // Example of Drupal behavior loaded.
  Drupal.behaviors.themeJS = {
    attach: function (context, settings) {
      if (typeof context['location'] !== 'undefined') { // Only fire on document load.

        /* code goes here */

      }
    }
  };

})(jQuery, Drupal, this, this.document);
```
