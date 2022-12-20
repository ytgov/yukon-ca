/**
 * @file
 * Task: test:pa11y.
 * Pa11y tests websites for accessibility issues. http://pa11y.org/.
 */

module.exports = (gulp, plugins, options) => {
  const { pa11y, gutil } = plugins;

  gulp.task('test:pa11y', (cb) => {
    // Initialising the initial values.
    let errors = 0;
    let warnings = 0;
    let passed = false;

    // Starting the audit.
    gutil.log('Accessibility Audit starts');

    // Initialising the test urls using pa11y function and options passed.
    const testpa11y = options.pa11y.urls.reduce(
      (arr, el) => (el.length ? [...arr, pa11y(el, options.pa11y)] : arr),
      [],
    ); // pure fn - immutable.

    // Using ES6 Promise to fetch all pa11y results.
    Promise.all(testpa11y)
      // success response.
      // eslint-disable-next-line consistent-return
      .then((results) => {
        // Iterating through results array.
        // eslint-disable-next-line array-callback-return
        results.map((result) => {
          // If results has issues.
          if (Object.keys(result.issues).length) {
            // Iterating through issues
            // eslint-disable-next-line array-callback-return
            result.issues.map((issue) => {
              // Defining the message template for issues
              const message = `\n================================================================================\n
                      ${result.pageUrl}\n
                      ${issue.type}\n
                      ${issue.code}\n
                      ${issue.context}\n
                      ${issue.message}\n
                      ${issue.selector}
                      \n================================================================================\n`;
              // Logging errors in red
              if (issue.type === 'error') {
                errors += 1;
                gutil.log(gutil.colors.red(message));
              } else if (issue.type === 'warning') {
                // Logging warnings in magenta
                warnings += 1;
                gutil.log(gutil.colors.magenta(message));
              }
            });
          } else {
            // If the obj type has different data type.
            gutil.log(result);
          }
        });
        // Logging of issues finishes

        // Updating the build response as per the promise response.
        // If error crosses threshold.
        if (options.pa11y.threshold.errors > -1 && errors > options.pa11y.threshold.errors) {
          cb(
            new gutil.PluginError(
              'pa11y',
              gutil.colors.red(
                `\n================================================================================\n
            Build failed due to accessibility errors exceeding threshold ( ${errors} errors) with a threshold of ${options.pa11y.threshold.errors}
            \n================================================================================\n
            ${errors} errors\n
            ${warnings} warnings\n`,
              ),
            ),
          );
        } else if (options.pa11y.threshold.warnings > -1 && warnings > options.pa11y.threshold.warnings
        ) {
          // If warnings crosses threshold.
          cb(
            new gutil.PluginError(
              'pa11y',
              gutil.colors.magenta(
                `\n================================================================================\n
            Build failed due to accessibility warnings exceeding threshold (${warnings} warnings) with a threshold of ${options.pa11y.threshold.warnings}
            \n================================================================================\n
            ${errors} errors\n
            ${warnings} warnings\n`,
              ),
            ),
          );
        } else {
          // In case of no warning and no error pass the build.
          gutil.log(
            'pa11y',
            gutil.colors.cyan(
              `\n================================================================================\n
          Build succeeded.
          \n================================================================================\n
          ${errors} errors\n
          ${warnings} warnings\n`,
            ),
          );

          // In case of pass, updating the passed status to true in case of passing
          passed = true;
        }

        // Breaking the gulp task if status is not true;
        if (!passed) {
          return 0;
        }
      })
      // Error handling code.
      .catch((error) => {
        gutil.log(error.message);
        return 0;
      });
  });
};
