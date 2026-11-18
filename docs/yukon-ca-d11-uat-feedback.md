# Yukon.ca Drupal 11 upgrade - UAT testing feedback summary

Compiled 2026-11-02 from Peter's Kellett inbox.
Environment tested: UAT (`https://yukon.cms-uat.yukon.ca`)
Repo refs (EXTRACTED from emails): `ytgov/yukon-ca`, branch `drupal-11-upgrade`, PR #1165, issue #1143, automated tests in `tests/playwright/README.md`

Labels: EXTRACTED = stated in an email. INFERRED = derived by me, reason given. UNKNOWN = not stated anywhere I could read.

## Sources

| ID | Date (UTC) | From | Subject | Notes |
|----|-----------|------|---------|-------|
| E1 | 2026-10-22 17:12 | Lily Gontard | Testing D11 on UAT | Public-facing results. Lily said she'd continue "over the next couple of days" |
| E2 | 2026-10-22 18:57 | Lily Gontard | More D11 testing results | Per-role editor results |
| E3 | 2026-10-22 21:41 | Lily Gontard | Last of D11 testing | Peter's full numbered test list (items 1-12) with Lily's inline results; refers back to E1/E2 for most items |

- All three were sent to Will Strong and Peter, cc Lee O'Mara. Will forwarded all three to Peter on 2026-11-02 (22:46 UTC) with no added text visible.
- Dave Rogers: no testing feedback found.
- Lee O'Mara: no D11 test results found (see "Other related emails" at the end).
- No reply from Peter or Will to any of the three is visible in these threads.
- The test list and results below are taken from Lily's pasted copies in the emails. I could not open the PDF attachments Peter originally sent (`d11-uat-test-plan.pdf`, `d11-uat-content-editors.pdf`, `d11-uat-public.pdf`, `d11-uat-translators.pdf`, `d11-automated-test-results.pdf`, sent 2026-09-14), so anything in those PDFs that Lily's emails don't mention is not covered here.
- Item numbers 1-12 are from E3. Mapping E1/E2 results onto them is INFERRED from matching content (E1/E2 use their own numbering).

## Status at a glance

| # | Test | Result |
|---|------|--------|
| 1 | Content moderation workflows | PASS with issues (A5, A6) |
| 2 | Paragraphs: translated content editing | NOT TESTED (waiting on French translation team) |
| 3 | FontAwesome icon picker (editing) | PASS |
| 4 | Workbench dashboard (EN/FR) | PASS in English; French not confirmed (G2) |
| 5 | Rich text editing (CKEditor 5) | PASS |
| 6 | Rebuild node access permission removed | PASS |
| 7 | Block visibility (feedback block, page title block, /documents PHP warning) | FAIL in E1; E3 contradicts (see A1, A2, A3) |
| 8 | Page feedback webform submission | PASS |
| 9 | FontAwesome icons on published pages | PASS |
| 10 | Rich text content rendering (old list type attr) | PARTIAL (A4) |
| 11 | Malformed taxonomy term URLs return 404 | PASS |
| 12 | Link field data fix | PASS with follow-up (A7) |

## Needs attention

### A1. Page feedback block shows on a page where it should be hidden
- Status: OPEN (see conflict note below)
- Test (item 7): the "was this page helpful" feedback block should be hidden on the campaign page and shown on news pages.
- URL: `https://yukon.cms-uat.yukon.ca/decide-you-ride-drive-sober`
- Expected: no feedback box near the bottom.
- Actual (E1, EXTRACTED): "Does not work as expected. The feedback box appears at the bottom."
- Control case passes: `/news/omicron-cases-increasing-quickly` shows the feedback box as expected.
- Context (EXTRACTED from E3): this relies on the core patch for negating the content type / taxonomy term block visibility condition, which the upgrade kept because two blocks depend on it.
- Cause: UNKNOWN. SUSPECTED (consistent with the context above, unverified): negated block visibility condition not being applied after the upgrade.
- CONFLICT: E3 (sent about 4.5 hours after E1) records "Looks as expected. No PHP error when checked docs or in reports" against item 7 as a whole. It is UNKNOWN whether Lily re-tested after E1, whether that note only covers the /documents PHP check (it is placed after that bullet), or whether it is an error. Needs confirming with Lily before treating A1-A3 as fixed or still failing.

### A2. Page title block shows on pages where it should be hidden
- Status: OPEN (same conflict note as A1)
- Test (item 7): page title heading should be hidden at the top of the content area on these pages.
- Failing (E1, EXTRACTED):
  - `https://yukon.cms-uat.yukon.ca/decide-you-ride-drive-sober` - "Page title appears at top of page."
  - `https://yukon.cms-uat.yukon.ca/blogs/digital-information-and-services` - "Page title appears at top of page." (blog_type taxonomy term page per E3)
- Passing (E1): `/` , `/engagements`, `/documents` hide the title correctly. News page `/news/omicron-cases-increasing-quickly` shows the title normally, as expected.
- Cause: UNKNOWN. Same SUSPECTED area as A1 (block visibility conditions on campaign_page and blog_type term pages).

### A3. /documents PHP warning check
- Status: PASS (E3, EXTRACTED): "No PHP error when checked docs or in reports." Listed here only because it sits under the conflicted item 7.
- Background (EXTRACTED from E3): the warning was `Undefined array key "field_category_target_id"` from custom module `yukon_w3_custom`, pre-existing, fixed in this deployment.

### A4. Second-level bullet style on old list content
- Status: OPEN
- Test (item 10): pages with old-style list markup (e.g. `<ol type="a">`) should display with correct formatting.
- URL: `https://yukon.cms-uat.yukon.ca/yukon-health-care-and-social-services-bursary`
- Actual (E1, EXTRACTED): "Second level bullets are solid black circles. Would expect different appearance (black outlined circle?)"
- E3 wording for the same page (EXTRACTED): "Note secondary bullet not outlined white circle". Lily describes the expected style slightly differently in the two emails (black outlined circle vs outlined white circle). Likely the same thing, but the exact expected style is UNKNOWN.
- The other page tested for this item passes: `/health-and-wellness/care-services/request-coverage-services-not-included-yukon-health-insurance-plan` ("Looks as expected").
- Cause: UNKNOWN. SUSPECTED (unverified): relates to the CKEditor 5 change that removed `<ul type>` / `<ol type>` from the allowed source-editing tags (E3 item 5 background), or a theme CSS change. Not confirmed whether D10 showed outlined bullets here.
- Screenshot attached to E3 (not reviewed by me).

### A5. Revision log message not mandatory on edits for some content types
- Status: OPEN, UNKNOWN whether this is an upgrade regression or pre-existing behavior
- Source: E2 item 1 (content moderation), roles Publisher and Site admin.
- Actual (EXTRACTED): "Revision log message field should be mandatory for edits on all content types (it's not on Campgrounds and Event)". For new pages it is not mandatory, which Lily says is correct.
- Affected content types: Campground Directory Record, Event.
- Affected roles reported: Publisher, Site admin. Writer and Editor reported "works as expected".
- Blog author (E2 item 2): the sentence reads "works as expected, but does not get Revision log message is not mandatory for editing Campaign pages". The meaning is unclear (garbled). Possibly the same issue on Campaign pages for Blog author. INFERRED, low confidence.
- Not stated: whether the requirement worked in D10 or whether the revision log requirement is part of the upgrade scope. This was not one of the expected results in the test list as pasted in E3.
- Screenshot attached to E2 (not reviewed by me).

### A6. Blog author role cannot edit content
- Status: OPEN, UNKNOWN whether regression or pre-existing
- Source: E2 item 1 (content moderation).
- Actual (EXTRACTED):
  - User with only the Blog author role "cannot edit anything (not even Blogs)".
  - User with Blog author + Publisher roles "cannot edit blogs".
- Not stated: which blog content type, exact UI behavior or error message, or whether this worked on D10/prod.
- Cause: UNKNOWN. SUSPECTED (unverified): moderation/workflow permission or transition access issue for blog content, given the removal of orphaned `moderation_state` base field override configs and the content_moderation patch replacement (E3 item 1 background). Not confirmed.
- Screenshot attached to E2 (not reviewed by me).

### A7. Two content items with an incomplete link value
- Status: OPEN
- Test (item 12): bad link field values missing a URI scheme were fixed sitewide. Two items could not be auto-corrected because the intended destination was ambiguous.
- Items (EXTRACTED from E3):
  - `https://yukon.cms-uat.yukon.ca/policy-benefits` - link field reads `growing-together`
  - `https://yukon.cms-uat.yukon.ca/bid-value-reductions` - link field reads `growing-together`
- Lily's result (EXTRACTED): "Tested. The URL was incomplete."
- Not stated: what the corrected URL should be, or who should fix it (Kellett data fix vs Yukon editors).
- INFERRED (weak): a page exists at `https://yukon.ca/en/growing-together` (mentioned in Lily's 2026-10-30 "Another work item" email), so that may be the intended target. Not confirmed.
- The main test for this item passes: `/doing-business/funding-and-supports-business/mining-investment-yukon` loads and the Twitter/X link works.

## Not yet tested / gaps

### G1. French translation testing (item 2)
- Status: PENDING
- Test: open French translation of a `multi_step_page` node with a `multi_step` paragraph containing nested "chart" items. Confirm Add/Remove buttons for nested chart items are NOT shown in the French edit form (expected after the paragraphs patches were dropped), and that existing chart items display and edit correctly in EN and FR. Test as Translator or Editor.
- Result (E3, EXTRACTED): "Waiting on FrenchTranslation team to test. They're short-staffed and we may deploy D11 before they test and pick up the pieces afterwards."
- Decision stated (EXTRACTED): Lily indicated D11 may go to prod before this is tested, with fixes after. No explicit go/no-go beyond that.

### G2. French wording on Workbench dashboard (item 4)
- E2 reports "works as expected" for all roles on the Workbench dashboard and says nothing specific about the French version. Peter's list asked for a French check and offered five newly written French strings for review by someone fluent.
- Whether the French dashboard was checked at all: UNKNOWN. Whether the French wording was reviewed by a fluent speaker: UNKNOWN.

### G3. Translator role
- The test list calls for Translator testing (item 2). E2 reports on Writer, Editor, Publisher, Blog author and Site admin only. No Translator result in any email.

### G4. Anonymous vs logged-in
- Items 7, 9, 10, 11, 12 are listed as "test as anonymous". E1 does not say which session Lily used. UNKNOWN.

## Passed (short)

- **Item 1, content moderation:** Writer pass, Editor pass, Publisher pass (revision log caveat, A5), Site admin pass (A5), Blog author fail (A6).
- **Item 3, icon picker, edit form (all 5 sample pages):** Writer, Editor, Publisher, Site admin all pass. Blog author passes (A5 note only).
- **Item 4, Workbench dashboard (EN):** all five roles pass. French see G2.
- **Item 5, CKEditor 5 (lists, heading styles):** all five roles pass.
- **Item 6, "Rebuild content access permissions" revoked from all six roles:** Site admin confirmed none have it checked.
- **Item 8, page feedback webform:** both submissions (no query string, and `?ref=test`) work as expected.
- **Item 9, FontAwesome icons on published pages:** all 5 sample pages look as expected, no errors.
- **Item 10 (second page only):** `/health-and-wellness/care-services/request-coverage-services-not-included-yukon-health-insurance-plan` looks as expected.
- **Item 11, `/taxonomy/term/999999999`:** normal "page not found", not a server error.
- **Item 12 (main page):** `mining-investment-yukon` loads, Twitter/X link works.
- **Item 7 (passing parts):** `/`, `/engagements`, `/documents` hide the page title; news page shows feedback box and title; no PHP error on `/documents` or in dblog.

Sample pages used for items 3 and 9: `/decide-you-ride-drive-sober`, `/winter-driving-road-safety-awareness`, `/40-assets`, `/yukon-grown`, `/restoring-shakwak-corridor`.

## Other related emails (not test results)

- **2026-09-08, Lee O'Mara, "Re: Drupal 11 Procedures and Risk Assessment":** approved proceeding with the D11 upgrade and accepted removal of the TMGMT Diff module (EXTRACTED: he does not expect a substantial impact on translation work since TMGMT is mostly used for larger new pages).
- **2026-10-20, Lee O'Mara, "Check for Unison synchronization after PROD deployments":** Unison sync between cms-prd-web01 and -web02 failed the night of a Yukon.ca PROD deployment. Lee asked that after finishing PROD deployments, `sudo tail -f /var/www/.unison/unison.log` be checked for "0 failed" and any errors reported to him or Dave. Not stated whether that was acknowledged.
- **2026-10-15, Lee O'Mara, "Re: TEST server Status":** TEST can be put on any branch (about Will's staging branch plan).
