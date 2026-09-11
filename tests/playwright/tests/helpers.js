const { execSync } = require('child_process');

/**
 * Logs a page in as the given Drupal username via a freshly-generated
 * one-time login link (drush uli). Requires ddev to be running and this
 * repo to be the current project. A fresh link is generated on every call
 * so re-running against multiple browsers/projects never reuses a
 * one-time link.
 */
async function loginAs(page, username, baseURL) {
  const output = execSync(
    `ddev drush uli --name="${username}" --uri="${baseURL}"`,
    { cwd: `${__dirname}/../../..`, encoding: 'utf8' }
  ).trim();
  const url = output.split('\n').pop();
  await page.goto(url);
}

module.exports = { loginAs };
