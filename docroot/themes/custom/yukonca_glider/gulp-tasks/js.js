import { src, dest }        from 'gulp';
import babel                from 'gulp-babel';
import uglify               from 'gulp-uglify';
import sourcemaps           from 'gulp-sourcemaps';
import plumber              from 'gulp-plumber';
import rename               from 'gulp-rename';
import del                  from 'del';
import { isDevelopmentEnv } from './env';
import { jsOptions }        from './options';

const renameOptions       = { suffix: '.min' };

const compileJs = () => {
  let task = src([jsOptions.files, `!${jsOptions.bundlesFiles}`]);

  if (isDevelopmentEnv) {
    task = task.pipe(plumber());
  }

  task = task.pipe(sourcemaps.init())
    .pipe(babel());

  if (!isDevelopmentEnv) {
    task = task
      .pipe(uglify({ mangle: { reserved: ['Drupal'] } }))
      .pipe(rename(renameOptions))
      .pipe(sourcemaps.write('./maps'));
  } else {
    task = task.pipe(rename(renameOptions));
  }

  if (isDevelopmentEnv) {
    task = task.pipe(plumber.stop());
  }

  return task.pipe(dest(jsOptions.destination));
};

const cleanJs = (done) => {
  del.sync(jsOptions.destination);
  done();
};

export {
  compileJs,
  cleanJs,
};
