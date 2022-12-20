import { src }              from 'gulp';
import plumber              from 'gulp-plumber';
import eslint               from 'gulp-eslint';
import { isDevelopmentEnv } from './env';
import {
  jsOptions,
  jsLintingOptions,
} from './options';

const filesToWatch = [
  jsOptions.files,
  ...jsLintingOptions.files.gulp,
];

const lintJsTask = (source) => {
  let task = src(source);

  if (isDevelopmentEnv) {
    task = task.pipe(plumber());
  }

  task = task.pipe(eslint())
    .pipe(eslint.format());

  if (isDevelopmentEnv) {
    task = task.pipe(plumber.stop());
  }

  return task;
};

const lintJs = () => lintJsTask(filesToWatch);

export {
  lintJsTask,
  lintJs,
};
