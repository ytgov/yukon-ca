import { src, dest }  from 'gulp';
import gulpSass       from 'gulp-sass';
import dartSass       from 'sass';
import autoprefixer   from 'gulp-autoprefixer';
import sourcemaps     from 'gulp-sourcemaps';
import plumber        from 'gulp-plumber';
import rename         from 'gulp-rename';
import cleanCSS       from 'gulp-clean-css';
import del            from 'del';
import webpack        from 'webpack';
import webpackStream  from 'webpack-stream';
import postcss        from 'gulp-postcss';
import {
  isDevelopmentEnv,
  isProductionEnv,
} from './env';

import {
  paths,
  sassOptions,
  patternsOptions,
} from './options';

import { lintCssTask }  from './lint-css';
import { lintJsTask }   from './lint-js';

const renameOptions       = { suffix: '.min' };
const cleanCssOptions     = { compatibility: '*' }; // Default compatibility Internet Explorer 10+ compatibility mode
const autoprefixerOptions = { cascade: false };
const sass                = gulpSass(dartSass);

const compilePatternsJs = () => {
  let task = src([...patternsOptions.js.src]);

  if (patternsOptions.webpackConfig && patternsOptions.webpackConfig.length) {
    if (isDevelopmentEnv) {
      task = task.pipe(plumber()).pipe(sourcemaps.init());
    }

    task = task.pipe(webpackStream({ config: patternsOptions.webpackConfig }, webpack));

    if (isDevelopmentEnv) {
      task = task
        .pipe(sourcemaps.write('.'))
        .pipe(plumber.stop());
    }
  }

  return task.pipe(dest('./'));
};

const compilePatternsSass = (done) => {
  let task = src([patternsOptions.css.src, patternsOptions.css.ommit]);

  if (isDevelopmentEnv) {
    task = task.pipe(plumber());
  }

  task = task.pipe(sourcemaps.init())
    .pipe(sass(sassOptions.compilerOptions).on('error', isDevelopmentEnv ? sass.logError : done))
    .pipe(postcss()).on('error', (e) => done(e))
    .pipe(autoprefixer(autoprefixerOptions));

  if (isProductionEnv) {
    task = task.pipe(cleanCSS(cleanCssOptions));
  }

  task = task.pipe(rename(renameOptions))
    .pipe(sourcemaps.write('.'))
    .pipe(rename((scssPath) => {
      scssPath.dirname = scssPath.dirname.replace('scss', paths.styles.destination);
    }));

  if (isDevelopmentEnv) {
    task = task.pipe(plumber.stop());
  }

  return task.pipe(dest(patternsOptions.css.dest));
};

const cleanPatterns = (done) => {
  del.sync(patternsOptions.clean);
  done();
};

const lintPatternsCss = () => lintCssTask(patternsOptions.css.src);
const lintPatternsJs  = () => lintJsTask(...patternsOptions.js.src);

export {
  compilePatternsSass,
  compilePatternsJs,
  cleanPatterns,
  lintPatternsCss,
  lintPatternsJs,
};
