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

const PHP_ERROR_PATTERNS = [
  /Warning:/,
  /Notice:/,
  /Deprecated:/,
  /Fatal error/,
  /TypeError:/,
  /Undefined array key/,
  /Undefined variable/,
  /Undefined property/,
];

/**
 * Fails the test if the current page's rendered HTML contains a PHP
 * error/warning/notice string. Drupal can render these inline in the page
 * body without necessarily returning a non-200 status or the generic
 * "unexpected error" message, so status-code and known-string checks alone
 * can miss them.
 */
async function assertNoPhpErrors(page, label) {
  const bodyText = await page.textContent('body');
  for (const pattern of PHP_ERROR_PATTERNS) {
    const match = bodyText.match(pattern);
    if (match) {
      const index = bodyText.indexOf(match[0]);
      const snippet = bodyText.slice(Math.max(0, index - 80), index + 120);
      throw new Error(`PHP error/warning found on ${label || page.url()}: "${snippet.trim()}"`);
    }
  }
}

module.exports = { loginAs, assertNoPhpErrors };
