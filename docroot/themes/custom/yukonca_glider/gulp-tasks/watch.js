import {
  series,
  watch as gWatch,
  parallel,
} from 'gulp';
import { compileSass }  from './sass';
import { lintCss }      from './lint-css';
import { compileJs }    from './js';
import { lintJs }       from './lint-js';
import makeIconsFont    from './icons';
import {
  sassOptions,
  jsOptions,
  jsLintingOptions,
  patternsOptions,
} from './options';
import {
  compilePatternsSass,
  compilePatternsJs,
  lintPatternsCss,
  lintPatternsJs,
} from './patterns';
import compileJsBundles from './js-bundles';

const watchOptions  = { ignoreInitial: true };
const patternsFiles = [
  patternsOptions.css.src,
  ...patternsOptions.js.src,
];

const taskSeries = {
  sass          : series(lintCss, compileSass),
  js            : series(parallel(series(lintJs, compileJs), compileJsBundles)),
  patternsSass  : series(lintPatternsCss, compilePatternsSass),
  patternsJs    : series(lintPatternsJs, compilePatternsJs),
};

const watchSass         = () => gWatch([
  sassOptions.files,
  ...sassOptions.filesToWatch,
], watchOptions, taskSeries.sass);
const watchJs           = () => gWatch([jsOptions.files, ...jsLintingOptions.files.gulp], watchOptions, taskSeries.js);
const watchPatternsSass = () => gWatch([patternsOptions.css.src], watchOptions, taskSeries.patternsSass);
const watchPatternsJs   = () => gWatch([...patternsOptions.js.src], watchOptions, taskSeries.patternsJs);
const watchPatterns     = () => gWatch(patternsFiles, watchOptions, parallel(watchPatternsSass, watchPatternsJs));

const watch             = (done) => {
  watchSass();
  watchJs();
  watchPatternsSass();
  watchPatternsJs();
  makeIconsFont(done);
};

export {
  watchSass,
  watchPatternsSass,
  watchJs,
  watchPatternsJs,
  watchPatterns,
  watch,
};
