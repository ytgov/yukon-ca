import { src, dest }        from 'gulp';
import sourcemaps           from 'gulp-sourcemaps';
import plumber              from 'gulp-plumber';
import webpack              from 'webpack';
import webpackStream        from 'webpack-stream';
import { isDevelopmentEnv } from './env';
import {
  jsOptions,
  webpackConfig,
} from './options';

const compileJsBundles = () => {
  let task = src([jsOptions.files]);

  if (isDevelopmentEnv) {
    task = task.pipe(plumber()).pipe(sourcemaps.init());
  }

  task = task.pipe(webpackStream(webpackConfig, webpack));

  if (isDevelopmentEnv) {
    task = task
      .pipe(sourcemaps.write('.'))
      .pipe(plumber.stop());
  }

  return task.pipe(dest(jsOptions.destination));
};

export default compileJsBundles;
