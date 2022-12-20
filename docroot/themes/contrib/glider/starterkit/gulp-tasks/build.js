import {
  series,
  parallel,
} from 'gulp';

import { compileSass } from './sass';
import { lintCss }     from './lint-css';
import { compileJs }   from './js';
import { lintJs }      from './lint-js';
import makeIconsFont   from './icons';
import {
  compilePatternsSass,
  compilePatternsJs,
  lintPatternsCss,
  lintPatternsJs,
} from './patterns';
import compileJsBundles from './js-bundles';

const cssTasks = [
  makeIconsFont,
  compileSass,
  lintCss,
];

const jsTasks = [
  compileJs,
  compileJsBundles,
  lintJs,
];

const patternsTasks = [
  lintPatternsCss,
  compilePatternsSass,
  lintPatternsJs,
  compilePatternsJs,
];

const build = (done) => parallel(series(parallel(jsTasks), series(cssTasks)), parallel(patternsTasks))(done);

export default build;
