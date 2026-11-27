# D11 upgrade: UAT test plan

This is a targeted list of things to check on UAT after the Drupal 11 upgrade is deployed there, based on what actually changed in this upgrade. It's split into two parts: content-management features (what editors and administrators use to manage content day to day) and front-end/public-facing behavior (what any visitor, logged in or anonymous, could notice). It doesn't cover developer-only config verification (e.g. confirming a config schema fix applied correctly) — that's implicitly covered by config export/deployment working at all. Module removals that were already discussed and accepted (e.g. `tmgmt_diff`) aren't repeated here.

Test as more than one role where noted. Existing site accounts for each role should be fine — no new test users should be needed.

URLs below use the UAT base `https://yukon.cms-uat.yukon.ca`. Every path was pulled from this site's own database (actual aliases, not `/node/N` paths) and confirmed to load locally as an anonymous visitor before including it here.

## Part 1: Content management

## 1. Content moderation and translation workflows

**Why:** the upgrade removed a set of broken `moderation_state` base field override configs (orphaned config left behind by `content_moderation`, unrelated to any content) and replaced the patch that caused them with the real upstream fix, which also prevents new ones from being created.

**Test as:** Editor, Publisher, Writer, Blog Author, Site Administrator (whichever roles normally move content through workflow).

- Create/edit content on each of the affected content types — `campground_directory_record`, `department`, `directory_records_places`, `event`, `news` — and move it through the moderation workflow (Draft → Needs Review → Published, or whatever your states are) as normal.
- Confirm the moderation state field still appears and behaves normally on the edit form for these content types specifically (they're the ones whose stray override configs were cleaned up).

## 2. Paragraphs: translated content editing

**Why:** two `paragraphs` module patches were dropped. One reintroduced structural-edit buttons during translation editing that core deliberately hides (to prevent one language's edit from silently restructuring another language's content) — confirmed unused on this site, but worth a UAT sanity check since it changes edit-form behavior. The other patch added support for "asymmetric translations" — letting a translation have a different number/order of paragraph items than the original — but that capability was never actually set up on this site (it requires a companion module that isn't installed), so it was a no-op here; the two patches only mattered together, and only if asymmetric translation had been fully configured, which it wasn't.

**Test as:** Translator, Editor (or whoever edits French content).

- Open the French translation of a `multi_step_page` node that has a `multi_step` paragraph with nested "chart" items.
- Confirm the "Add" / "Remove" buttons for the nested chart items are **not shown** while editing the French translation — this is expected, correct behavior now, not a bug. (Before the patches were dropped, those buttons briefly appeared but were never actually used to add/remove nested items.)
- Confirm the existing chart items still display and edit correctly in both English and French.

## 3. FontAwesome icon picker on navigation/jump-point paragraphs

**Why:** 199 `navigation_jump_point` paragraphs (the only paragraph type this affects) had empty/legacy icon settings data (`NULL`, left by a legacy import that bypassed normal field validation) that would otherwise throw a fatal error on render — affecting 40 nodes in total, 20 published and 20 unpublished. Rather than keep a patch to work around it, a post-update hook backfills those `NULL` values with the same empty settings the icon widget itself would have saved, and the patch has been removed. Since this replaces a patch with a direct data fix, it's worth verifying the fix actually applied cleanly and the crash doesn't recur, rather than just confirming a patch didn't regress anything. Testing every affected node isn't practical, so below is a representative sample rather than the full list.

**Test as:** Editor, Publisher, Writer, Site Administrator.

- Open a "jump point"/navigation paragraph's edit form and confirm the icon picker still works normally when adding or changing an icon.
- Open the edit form for each of these existing pages (they have jump-point/navigation paragraphs with older icon data — exactly what the crash fix addresses) and confirm the icons render correctly in the editing preview:
  - <https://yukon.cms-uat.yukon.ca/decide-you-ride-drive-sober>
  - <https://yukon.cms-uat.yukon.ca/winter-driving-road-safety-awareness>
  - <https://yukon.cms-uat.yukon.ca/40-assets>
  - <https://yukon.cms-uat.yukon.ca/yukon-grown>
  - <https://yukon.cms-uat.yukon.ca/restoring-shakwak-corridor>

## 4. Workbench admin dashboard views (English and French)

**Why:** these views had real data corruption (`content: Array`) in both their English and French config, unrelated to any patch, found and fixed as part of this upgrade. The French versions had never had correct data captured at all, so the French text below is newly written for this fix rather than recovered from an original source.

**Test as:** any role with access to the Workbench dashboard (`/admin/content` overview blocks, or wherever these views/blocks are placed).

- View "Workbench: Current user" (profile heading), "Workbench: Edited" (my edits / empty state), and "Workbench: Recent content" (recent content / empty state) in English — confirm the headings and empty-state messages render correctly, not literally as `Array`.
- Switch the admin UI language to French (or view as a French-speaking editor) and check the same three views — confirm the French text isn't corrupted. It's admin-only dashboard text, so it's up to you whether the exact wording is worth a closer look by someone fluent — it's not recovered from an original source, so there's nothing "original" to restore, just newly written text:
  - "Profil de {{ name }}"
  - "Vous n'avez créé ni modifié aucun contenu."
  - "Vos modifications récentes"
  - "Il n'y a aucun contenu disponible que vous puissiez modifier."
  - "Contenu récent"

## 5. Rich text editing (CKEditor 5)

**Why:** the core upgrade changed some CKEditor 5 configuration: heading/list "styles" support was added, and `<ul type>`/`<ol type>` were removed from the allowed source-editing tags for Basic HTML.

**Test as:** Editor, Publisher, Writer, Blog Author, Site Administrator (any role that edits body content with Basic HTML or Full HTML).

- Create/edit content using ordered and unordered lists, and confirm they still display and save correctly.
- If any existing content relies on old-style HTML list numbering via a `type` attribute (e.g. `<ol type="a">` for alphabetic lists), check that content still displays as expected — re-saving it through the WYSIWYG editor could strip that attribute now that it's no longer in the allowed source-editing tag list.
- Try the heading styles options in the editor toolbar and confirm they apply and save correctly.

## 6. "Rebuild node access permissions" capability

**Why:** core's Drupal 11 upgrade path introduces this permission as newly separate from "Administer content," and automatically grants it to any role that already had "Administer content" (except a role already flagged as the full site administrator). As such, `Blog Author`, `Editor`, `Publisher`, `Site Administrator`, `Translator`, and `Writer` all picked it up during the upgrade's initial database updates — they all already had "Administer content" before this upgrade. Core itself marks this new permission as restricted/trusted-only, with the description "Trigger a content access permission rebuild. This can be a potentially long and disruptive process." We therefore revoked it from all 6 roles, since it does not seem like something these roles need, leaving only the pre-existing "Administer content" permission in place.

**Test as:** Site Administrator.

- Confirm on `admin/people/permissions` that none of `Blog Author`, `Editor`, `Publisher`, `Site Administrator`, `Translator`, or `Writer` have "Rebuild content access permissions" checked.

## Part 2: Front-end / public browsing (logged in and anonymous)

## 7. Block visibility on specific pages

**Why:** a core patch that adds support for *negating* the "content type"/"taxonomy term" visibility condition on blocks was reviewed and kept, since two blocks depend on it. This is core behavior the upgrade touches, not something content editors configure, but it directly affects what any visitor sees on a page.

Also found while testing this, unrelated to the patch: visiting `/documents` with no filters applied triggered a PHP warning (`Undefined array key "field_category_target_id"`) from this site's own custom `yukon_w3_custom` module — pre-existing, not caused by the upgrade, but a quick fix so it's included with this deployment. Since `/documents` is already one of the pages visited below, check it for this too rather than as a separate test.

**Test as:** anonymous is sufficient for this.

- **Page feedback block**: visit the `campaign_page` below and confirm the feedback block is hidden there (it should be, per its negated condition), then visit the `news` page and confirm it shows.
  - Hidden: <https://yukon.cms-uat.yukon.ca/decide-you-ride-drive-sober>
  - Shown: <https://yukon.cms-uat.yukon.ca/news/omicron-cases-increasing-quickly>
- **Page title block**: visit the front page, `/engagements`, and `/documents` — the page title block should be hidden on all three. Visit the `campaign_page` and `blog_type` taxonomy term page below — title block should be hidden there too. Visit the ordinary news page and confirm the title block shows normally.
  - Hidden: <https://yukon.cms-uat.yukon.ca/>
  - Hidden: <https://yukon.cms-uat.yukon.ca/engagements>
  - Hidden: <https://yukon.cms-uat.yukon.ca/documents>
  - Hidden: <https://yukon.cms-uat.yukon.ca/decide-you-ride-drive-sober>
  - Hidden: <https://yukon.cms-uat.yukon.ca/blogs/digital-information-and-services>
  - Shown: <https://yukon.cms-uat.yukon.ca/news/omicron-cases-increasing-quickly>
- **PHP error check on `/documents`**: confirm the page itself shows no visible PHP error/warning text. UAT and production are normally configured not to display errors on the page at all, so also check **Reports → Recent log messages** (`/admin/reports/dblog`) for any new PHP warning logged around the time you visited — if error display is off, that's the only place it would show up.

## 8. Page feedback webform submission

**Why:** kept a patch fixing a token (`[current-page:query:query]`) that otherwise leaks raw token text into submitted data when there's no query string.

**Test as:** anonymous or any logged-in role.

- Submit the page feedback form from <https://yukon.cms-uat.yukon.ca/news/omicron-cases-increasing-quickly> (no query string) and confirm the submission doesn't contain the literal text `[current-page:query:query]` anywhere.
- Submit it again from <https://yukon.cms-uat.yukon.ca/news/omicron-cases-increasing-quickly?ref=test> and confirm the query value is captured correctly.

## 9. FontAwesome icons on published pages

**Why:** same fix as item 3, but checking the actual rendered output on live pages rather than the edit form.

**Test as:** anonymous.

- Browse to the pages below (the same published pages as item 3) and confirm icons render without a server error anywhere on the page:
  - <https://yukon.cms-uat.yukon.ca/decide-you-ride-drive-sober>
  - <https://yukon.cms-uat.yukon.ca/winter-driving-road-safety-awareness>
  - <https://yukon.cms-uat.yukon.ca/40-assets>
  - <https://yukon.cms-uat.yukon.ca/yukon-grown>
  - <https://yukon.cms-uat.yukon.ca/restoring-shakwak-corridor>

## 10. Rich text content rendering

**Why:** same CKEditor 5 config change as item 5, but checking already-published content rather than the editing experience.

**Test as:** anonymous.

- Browse the pages below, which contain lists using the old `type` attribute style (e.g. `<ol type="a">`), and confirm they still display with correct formatting:
  - <https://yukon.cms-uat.yukon.ca/yukon-health-care-and-social-services-bursary>
  - <https://yukon.cms-uat.yukon.ca/health-and-wellness/care-services/request-coverage-services-not-included-yukon-health-insurance-plan>

## 11. Malformed taxonomy term URLs

**Why:** the theme's page-preprocess code had a crash (fatal 500 error) on malformed taxonomy term URLs; this is now fixed to correctly return a 404 instead.

**Test as:** anonymous.

- Visit a clearly invalid taxonomy path, e.g. `/taxonomy/term/999999999` or a `/taxonomy/term/<id>` for a non-existent term ID — confirm it returns a normal 404 page, not a server error.

## 12. Link field data fix (bad social-media links)

**Why:** testing this upgrade surfaced a pre-existing, unrelated bug: a link field value written by a legacy import (bypassing normal field validation) was missing `https://`, which crashed the page when rendered fresh. A sitewide fix for this class of problem — link values missing a URI scheme — is included with this deployment. See the GitHub issue comment on #1143 for the full write-up.

**Test as:** anonymous.

- Visit <https://yukon.cms-uat.yukon.ca/doing-business/funding-and-supports-business/mining-investment-yukon> and confirm the page loads normally (no server error), and that the Twitter/X link near the bottom of the content works and points somewhere sensible.
- Two content items could not be automatically corrected (the intended destination wasn't unambiguous) and are worth a manual look:
  - <https://yukon.cms-uat.yukon.ca/policy-benefits> — a link field currently reads "growing-together"
  - <https://yukon.cms-uat.yukon.ca/bid-value-reductions> — same, "growing-together"

## Optional: running this automatically, locally

If you'd rather not click through everything above by hand, most of it (aside from judgment calls like item 4's French text quality, item 6, and the two flagged links in item 12) is also covered by an automated Playwright test suite you can run yourself against a local copy of the site. See [`tests/playwright/README.md`](../tests/playwright/README.md) for setup and run instructions.
