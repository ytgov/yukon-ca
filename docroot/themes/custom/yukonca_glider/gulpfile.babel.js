import { compileSass, cleanCss }  from './gulp-tasks/sass';
import { lintCss }                from './gulp-tasks/lint-css';
import build                      from './gulp-tasks/build';
import { compileJs, cleanJs }     from './gulp-tasks/js';
import clean                      from './gulp-tasks/clean';
import { lintJs }                 from './gulp-tasks/lint-js';
import serve                      from './gulp-tasks/serve';
import compileJsBundles           from './gulp-tasks/js-bundles';
import makeIconsFont              from './gulp-tasks/icons';

import {
  cleanPatterns,
  compilePatternsJs,
  compilePatternsSass,
  lintPatternsCss,
  lintPatternsJs,
} from './gulp-tasks/patterns';

import {
  watch,
  watchJs,
  watchPatterns,
  watchPatternsJs,
  watchPatternsSass,
  watchSass,
} from './gulp-tasks/watch';

export default build;

export {
  build,
  clean,
  cleanCss,
  cleanJs,
  cleanPatterns,
  compileJs,
  compileJsBundles,
  compilePatternsJs,
  compilePatternsSass,
  compileSass,
  lintCss,
  lintJs,
  lintPatternsCss,
  lintPatternsJs,
  makeIconsFont,
  serve,
  watch,
  watchJs,
  watchPatterns,
  watchPatternsJs,
  watchPatternsSass,
  watchSass,
};
