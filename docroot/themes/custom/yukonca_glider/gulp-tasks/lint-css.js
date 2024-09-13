import { src }              from 'gulp';
import plumber              from 'gulp-plumber';
import stylelint            from 'gulp-stylelint';
import { isDevelopmentEnv } from './env';
import { sassOptions }      from './options';

const stylelintOptions = {
  failAfterError: !isDevelopmentEnv,
  reporters: [
    {
      formatter : 'string',
      console   : true,
    },
  ],
};

const lintCssTask = (source) => {
  let task = src([source]);

  if (isDevelopmentEnv) {
    task = task.pipe(plumber());
  }

  task = task.pipe(stylelint(stylelintOptions));

  if (isDevelopmentEnv) {
    task = task.pipe(plumber.stop());
  }

  return task;
};

const lintCss = () => lintCssTask(sassOptions.files);

export {
  lintCssTask,
  lintCss,
};
