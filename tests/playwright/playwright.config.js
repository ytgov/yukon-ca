// @ts-check
const { defineConfig, devices } = require('@playwright/test');

// Point BASE_URL at whatever environment you want to check
// (defaults to this project's local ddev URL).
const baseURL = process.env.BASE_URL || 'https://yukon.ca-d10.ddev.site:4433';

module.exports = defineConfig({
  testDir: './tests',
  fullyParallel: false,
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL,
    ignoreHTTPSErrors: true,
    headless: false,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
  ],
});
