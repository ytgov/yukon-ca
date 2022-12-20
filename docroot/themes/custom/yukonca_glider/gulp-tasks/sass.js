import { src, dest }  from 'gulp';
import gulpSass       from 'gulp-sass';
import dartSass       from 'sass';
import autoprefixer   from 'gulp-autoprefixer';
import sourcemaps     from 'gulp-sourcemaps';
import plumber        from 'gulp-plumber';
import rename         from 'gulp-rename';
import cleanCSS       from 'gulp-clean-css';
import del            from 'del';
import path           from 'path';
import postcss        from 'gulp-postcss';

import {
  isDevelopmentEnv,
  isProductionEnv,
} from './env';
import {
  sassOptions,
  cssOptions,
} from './options';

const renameOptions       = { suffix: '.min' };
const cleanCssOptions     = { compatibility: '*' }; // Default compatibility Internet Explorer 10+ compatibility mode
const autoprefixerOptions = { cascade: false };
const sass                = gulpSass(dartSass);

const compileSass = (done) => {
  let task = src(sassOptions.files);

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
    .pipe(sourcemaps.write('.'));

  if (isDevelopmentEnv) {
    task = task.pipe(plumber.stop());
  }

  return task.pipe(dest(sassOptions.destination));
};

const cleanCss = (done) => {
  del.sync(path.dirname(cssOptions.files));
  done();
};

export {
  compileSass,
  cleanCss,
};
