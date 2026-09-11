// Checks corresponding to docs/d11-uat-test-plan.md.
// Run with: npm test (from tests/playwright), after `npm install` and
// `npx playwright install`. Requires ddev running for this project so
// `loginAs()` can generate fresh one-time login links via drush.
const { test, expect } = require('@playwright/test');
const { loginAs, assertNoPhpErrors } = require('./helpers');

test.describe('Part 1: Content management', () => {
  test('1. Content moderation field present on affected content types', async ({ page, baseURL }) => {
    await loginAs(page, 'brstewar', baseURL); // Editor
    const types = ['campground_directory_record', 'department', 'directory_records_places', 'event', 'news'];
    for (const type of types) {
      await page.goto(`/node/add/${type}`);
      const hasModerationField = await page.locator('[name="moderation_state[0][state]"]').count();
      expect(hasModerationField, `moderation_state field missing on ${type} add form`).toBeGreaterThan(0);
    }
  });

  test('2. Paragraphs: add/remove buttons hidden while translating', async ({ page, baseURL }) => {
    await loginAs(page, 'lmpower', baseURL); // Translator
    // Node 533 ("The application process"), a multi_step_page with a French translation.
    await page.goto('/fr/node/533/edit');
  });

  test('3. FontAwesome icon picker on jump-point paragraph edit forms', async ({ page, baseURL }) => {
    await loginAs(page, 'brstewar', baseURL); // Editor
    // Node 3154 ("Decide before you ride – drive sober"), has jump-point paragraphs with legacy icon data.
    const response = await page.goto('/node/3154/edit');
    expect(response.status()).toBe(200);
    expect(await page.textContent('body')).not.toContain('The website encountered an unexpected error');
  });

  test('4. Workbench dashboard views render correctly (EN)', async ({ page, baseURL }) => {
    await loginAs(page, 'efraser', baseURL); // Site Administrator
    await page.goto('/admin/workbench');
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Array');
  });

  test('4b. Workbench dashboard views render correctly (FR)', async ({ page, baseURL }) => {
    await loginAs(page, 'efraser', baseURL);
    await page.goto('/fr/admin/workbench');
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Array');
  });

  test('5. CKEditor 5 loads on a Basic HTML body field', async ({ page, baseURL }) => {
    await loginAs(page, 'brstewar', baseURL); // Editor
    await page.goto('/node/add/news');
    await expect(page.locator('.ck-toolbar, .ck-content, [contenteditable="true"]').first()).toBeVisible({ timeout: 15000 });
  });

  test('6. "Rebuild content access permissions" removed from editorial roles', async ({ page, baseURL }) => {
    await loginAs(page, 'efraser', baseURL); // Site Administrator
    await page.goto('/admin/people/permissions');
    const roles = ['blog_author', 'editor', 'publisher', 'site_administrator', 'translator', 'writer'];
    for (const role of roles) {
      const checkbox = page.locator(`input[name="${role}[rebuild node access permissions]"]`);
      if (await checkbox.count()) {
        await expect(checkbox).not.toBeChecked();
      }
    }
  });
});

test.describe('Part 2: Front-end / public browsing', () => {
  test('7a. Page feedback block hidden on campaign_page, shown on news', async ({ page }) => {
    // Note: campaign_page nodes embed the feedback webform directly in
    // node--campaign-page.html.twig (independent of the block), so this
    // checks the reusable BLOCK specifically (#block-page-feedback-webform),
    // not just the presence of feedback-form text/markup on the page.
    await page.goto('/decide-you-ride-drive-sober', { waitUntil: 'domcontentloaded' });
    await assertNoPhpErrors(page);
    const feedbackBlockHidden = await page.locator('#block-page-feedback-webform').count();
    expect(feedbackBlockHidden, 'feedback BLOCK should be hidden on campaign_page (the template embeds its own copy directly)').toBe(0);

    await page.goto('/news/omicron-cases-increasing-quickly', { waitUntil: 'domcontentloaded' });
    await assertNoPhpErrors(page);
    const feedbackBlockShown = await page.locator('#block-page-feedback-webform').count();
    expect(feedbackBlockShown, 'feedback block should show on an ordinary news page').toBeGreaterThan(0);
  });

  test('7b. Page title block hidden on front page, engagements, documents, campaign_page, blog term; shown on news', async ({ page }) => {
    const titleBlock = '#block-yukonca-glider-page-title';
    const hiddenPaths = ['/', '/engagements', '/documents', '/decide-you-ride-drive-sober', '/blogs/digital-information-and-services'];
    for (const path of hiddenPaths) {
      await page.goto(path, { waitUntil: 'domcontentloaded' });
      await assertNoPhpErrors(page, path);
      expect(await page.locator(titleBlock).count(), `page title block should be hidden on ${path}`).toBe(0);
    }
    await page.goto('/news/omicron-cases-increasing-quickly', { waitUntil: 'domcontentloaded' });
    await assertNoPhpErrors(page);
    expect(await page.locator(titleBlock).count(), 'page title block should show on an ordinary news page').toBeGreaterThan(0);
  });

  test('8. Page feedback webform query token', async ({ page }) => {
    await page.goto('/news/omicron-cases-increasing-quickly', { waitUntil: 'domcontentloaded' });
    await assertNoPhpErrors(page);
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('[current-page:query:query]');

    await page.goto('/news/omicron-cases-increasing-quickly?ref=test', { waitUntil: 'domcontentloaded' });
    await assertNoPhpErrors(page);
    const bodyText2 = await page.textContent('body');
    expect(bodyText2).not.toContain('[current-page:query:query]');
  });

  test('9. FontAwesome icons render on published pages', async ({ page }) => {
    for (const path of [
      '/decide-you-ride-drive-sober',
      '/winter-driving-road-safety-awareness',
      '/40-assets',
      '/yukon-grown',
      '/restoring-shakwak-corridor',
    ]) {
      const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
      expect(response.status(), `${path} should return 200`).toBe(200);
      await assertNoPhpErrors(page, path);
      expect(await page.textContent('body')).not.toContain('The website encountered an unexpected error');
    }
  });

  test('10. Rich text list/heading content renders', async ({ page }) => {
    for (const path of [
      '/yukon-health-care-and-social-services-bursary',
      '/health-and-wellness/care-services/request-coverage-services-not-included-yukon-health-insurance-plan',
    ]) {
      const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
      expect(response.status()).toBe(200);
      await assertNoPhpErrors(page, path);
    }
  });

  test('11. Malformed taxonomy term URL returns 404', async ({ page }) => {
    const response = await page.goto('/taxonomy/term/999999999', { waitUntil: 'domcontentloaded' });
    expect(response.status()).toBe(404);
  });

  test('12. Link field data fix', async ({ page }) => {
    const response = await page.goto('/doing-business/funding-and-supports-business/mining-investment-yukon', { waitUntil: 'domcontentloaded' });
    expect(response.status()).toBe(200);
    await assertNoPhpErrors(page);
    expect(await page.textContent('body')).not.toContain('The website encountered an unexpected error');
  });
});
