# Available Gulp Tasks

## Table of Content

- [Tasks Options](#tasks-options)
- [Build](#build)
- [Compile](#compile)
  - [compileSass](#compilesass)
  - [compilePatternsSass](#compilepatternssass)
  - [compileJS](#compilejs)
  - [compileJsBundles](#compileJsBundles)
- [generateIcons](#generateIcons)
- [Linter](#linter)
  - [lintCss](#lintcss)
  - [lintJs](#lintjs)
  - [lintPatternsCss](#lintpatternscss)
  - [lintPatternsJs](#lintpatternsjs)
- [clean](#clean)
  - [cleanCss](#cleancss)
  - [cleanJs](#cleanjs)
- [watch](#watch)
  - [watchSass](#watchsass)
  - [watchPatternsSass](#watchpatternssass)
  - [watchJs](#watchjs)
  - [watchPatternsJs](#watchpatternsjs)
  - [watchPatterns](#watchpatterns)
- [Complete list of tasks](#complete-list-of-tasks)
- [Environments](#environments)
  - [Caveats](#caveats)

## Tasks Options

All the task options and routes we need in the different tasks are located in the file [options.js](./options.js)

Inside the options file we could find the following configuration:

- paths
- browserSyncOptions
- cssOptions
- sassOptions
- jsOptions
- imagesOptions
- jsLintingOptions
- pa11yOptions
- patternsOptions
- iconFontOptions

> If you need to modify/add something related to routes you should do the change in the [options.js](./options.js) file

## Build

This is de default task in order to execute all the available task in order to get the files transpiled and optimized.

```bash
npm run build
# or
npm run gulp build
```

## Compile

### compileSass

Compile all the `*.scss` files inside the folder `<themeRoot>/src/sass/`.

```bash
npm run compile:sass
# or
npm run gulp compileSass
```

### compilePatternsSass

Compile all the `*.scss` files inside the Patterns folder `<themeRoot>/patterns/`.

```bash
npm run compile:patterns:sass
# or
npm run gulp compilePatternsSass
```

### compileJS

Compile all the `*.js` files inside the folder `<themeRoot>/src/js/`. The output file will be `[filename].min.js` in the `dist/js` folder.

```bash
npm run compile:js
# or
npm run gulp compileJS
```

### compileJsBundles

Compile all the `*.js` files inside the folder `<themeRoot>/src/js/bundles/`.  The output file will be `[filename].bundle.min.js` in the `dist/js` folder.

```bash
npm run compile:bundles:js
# or
npm run gulp compileJsBundles
```

## generateIcons

This task will convert all the `.svg` files from the folder `<themeRoot>/src/icons/` into a custom font. The font by default will be named `themeIcons`. Fonts will be placed on the folder `<themeRoot>/dist/fonts/` and the generated styles should be in the file `<themeRoot>/src/sass/_themeIcons.scss`.

Some options could be configured in the [options file](./options.js).

## Linter

### lintCss

Run the linter on the `*.scss` files inside the folder `<themeRoot>/src/sass/`.

```bash
npm run lint:css
# or
npm run gulp lintCss
```

### lintJs

Run the linter on the `*.js` files inside the folder `<themeRoot>/src/js/`, `<themeRoot>/gulp-tasks/` and, `gulp.babel.js`;

```bash
npm run lint:js
# or
npm run gulp lintJs
```

### lintPatternsCss

Run the linter on the `*.scss` files inside the folder `<themeRoot>/patterns/**/src/sass/`.

```bash
npm run lint:patterns:css
# or
npm run gulp lintPatternsCss
```

### lintPatternsJs

Run the linter on the `*.js` files inside the folder `<themeRoot>/patterns/**/src/js/`.

```bash
npm run lint:patterns:js
# or
npm run gulp lintPatternsJs
```

## clean

With clean task you could remove all compiled .css and .js files. This is a shortcut to run all the clean tasks listed below.

```bash
npm run clean
# or
npm run gulp clean
```

### cleanCss

Remove all the `.css` and `.map` files in the folder `<themeRoot>/dist/css/`.

```bash
npm run clean:css
# or
npm run gulp cleanCss
```

### cleanJs

Remove all the `.css` and `.map` files in the folder `<themeRoot>/dist/js/`.

```bash
npm run clean:js
# or
npm run gulp cleanJs
```

## watch

The default `watch` task will watch all the task in order to transpile files and reload the browser when detect file changes.

watch task also runs `browserSync.reload` but, this just work if you run `serve` task beacuse in the serve task the server is initalized.

```bash
npm run watch
# or
npm run gulp watch
```

### watchSass

Watcher on the theme's sass files and compile and lint all the scss files in each change.

```bash
npm run watch:sass
# or
npm run gulp watchSass
```

### watchPatternsSass

Watcher on the patterns's sass files and compile and lint all the scss files in each change.

```bash
npm run watch:patterns:sass
# or
npm run gulp watchPatternsSass
```

### watchJs

Watcher on the theme's js files and compile and lint all the js files in each change.

```bash
npm run watch:js
# or
npm run gulp watchJs
```

### watchPatternsJs

Watcher on the patterns' js files and compile and lint all the js files in each change.

```bash
npm run watch:patterns:js
# or
npm run gulp watchPatternsJs
```

### watchPatterns

Run the `watchPatternsSass` and `watchPatternsJs` watchers.

```bash
npm run watch:patterns
# or
npm run gulp watchPatterns
```

## Complete list of tasks

| Gulp Task         | npm Script Command            | Task Location                   |
|-------------------|-------------------------------|---------------------------------|
|serve              |`npm run serve`                |[serve.js](./serve.js)           |
|build              |`npm run build`                |[build.js](./build.js)           |
|compileSass        |`npm run compile:sass`         |[sass.js](./sass.js)             |
|compilePatternsSass|`npm run compile:patterns:sass`|[patterns.js](./patterns.js)     |
|generateIcons      |`npm run generateIcons`        |[webfonts.js](./webfonts.js)     |
|compileJs          |`npm run compile:js`           |[js.js](./js.js)                 |
|compileJsBundles   |`npm run compile:bundles:js`   |[js-bundles.js](./js-bundles.js) |
|cleanCss           |`npm run clean:css`            |[sass.js](./sass.js)             |
|cleanPatternsCss   |`npm run clean:patterns:css`   |[patterns.js](./patterns.js)     |
|cleanJs            |`npm run clean:js`             |[js.js](./js.js)                 |
|cleanPatterns      |`npm run clean:patterns`       |[patterns.js](./patterns.js)     |
|lintCss            |`npm run lint:css`             |[lint-css.js](./lint-css.js)     |
|lintJs             |`npm run lint:js`              |[lint-js.js](./lint-js.js)       |
|lintPatternsCss    |`npm run lint:patterns:css`    |[patterns.js](./patterns.js)     |
|lintPatternsJs     |`npm run lint:patterns:js`     |[patterns.js](./patterns.js)     |
|watch              |`npm run watch`                |[watch.js](./watch.js)           |
|watchSass          |`npm run watch:sass`           |[watch.js](./watch.js)           |
|watchPatternsSass  |`npm run watch:patterns:sass`  |[watch.js](./watch.js)           |
|watchJs            |`npm run watch:js`             |[watch.js](./watch.js)           |
|watchPatternsJs    |`npm run watch:patterns:js`    |[watch.js](./watch.js)           |
|watchPatterns      |`npm run watch:patterns`       |[watch.js](./watch.js)           |

## Environments

All the task accept `development` and `production` environments, then you could call any task by using `cross-env` dependency or use some script in the package.json like this:

```bash
# To build all the tasks on production mode
npm run build

# To build all the tasks on dev mode
npm run build:dev

# To compile sass in production mode
npm run compile:sass

# To compile sass in dev mode
npm run compile:sass:dev

# To watch files in production mode
npm run watch

# To watch files in dev mode
npm run watch:dev
```

### Caveats

Notice some process will kill the watcher if those runs on `production` mode and that is the expected behavior.
