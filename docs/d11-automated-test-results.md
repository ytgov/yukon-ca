# D11 upgrade: automated test results

Automated run of every item in [`docs/d11-uat-test-plan.md`](d11-uat-test-plan.md), executed locally against this branch's ddev environment (not UAT, which isn't deployed yet) using the Playwright suite in [`tests/playwright/`](../tests/playwright), in both headed Chromium and headed Firefox. Role-based checks logged in via freshly-generated one-time login links (`drush uli`) for a representative user of each role: `brstewar` (Editor), `taharvey`/`efraser` (Publisher/Site Administrator), `eabeecro` (Writer), `bkcross` (Blog Author), `lmpower` (Translator).

**Result: 15/15 checks pass on both browsers.** No functional regressions found.

## Part 1: Content management

| #   | Check                                                                                                                          | Result                                                                                                                                                                                                                         |
| --- | ------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1   | Moderation field present on `campground_directory_record`, `department`, `directory_records_places`, `event`, `news` add forms | ✅ Pass (both browsers)                                                                                                                                                                                                         |
| 2   | Add/Remove buttons hidden on nested "Charts" paragraphs while editing a French translation (node 533)                          | ✅ Pass — verified two ways: page loads correctly, and a targeted script searched the entire form for any button labeled "Ajouter"/"Supprimer"/"Retirer" and found zero                                                         |
| 3   | Icon picker on 5 sample jump-point paragraph edit forms (formerly `NULL`/legacy icon settings, now backfilled)                 | ✅ Pass — all 5 edit forms load cleanly; visually confirmed (screenshot) node 3154's icon name fields ("house", "car", "info") render correctly, and the previously-fixed Twitter/Facebook link fields now show clean full URLs |
| 4   | Workbench dashboard (EN)                                                                                                       | ✅ Pass — no literal "Array" text                                                                                                                                                                                               |
| 4b  | Workbench dashboard (FR)                                                                                                       | ✅ Pass — visually confirmed via screenshot: "Profil de efraser", "Vos modifications récentes", and "Contenu récent" all render as normal-looking French, not corrupted.                                                        |
| 5   | CKEditor 5 loads on a Basic HTML body field                                                                                    | ✅ Pass — full toolbar and editable area confirmed visually                                                                                                                                                                     |
| 6   | "Rebuild content access permissions" removed from all 6 editorial roles                                                        | ✅ Pass                                                                                                                                                                                                                         |

## Part 2: Front-end / public browsing

| #   | Check                                                                                                                                                     | Result |
| --- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | ------ |
| 7a  | Feedback block hidden on campaign_page, shown on news                                                                                                     | ✅ Pass |
| 7b  | Page title block hidden on front page/`/engagements`/`/documents`/campaign_page/blog term, shown on news                                                  | ✅ Pass |
| 7c  | No PHP error/warning rendered on `/documents` (new: a pre-existing `yukon_w3_custom` bug, unrelated to the upgrade, was found and fixed here — see below) | ✅ Pass |
| 8   | Feedback webform query token doesn't leak literal `[current-page:query:query]`                                                                            | ✅ Pass |
| 9   | FontAwesome icons render on 5 sample published pages, no server error or PHP warning                                                                      | ✅ Pass |
| 10  | Rich text list/heading pages load without error                                                                                                           | ✅ Pass |
| 11  | Malformed taxonomy term URL (`/taxonomy/term/999999999`) returns 404                                                                                      | ✅ Pass |
| 12  | Link field fix — mining-investment-yukon page loads, Twitter link works                                                                                   | ✅ Pass |

Tests now also check every front-end page they visit for PHP error/warning/notice text rendered on the page, not just server error status codes — this is what caught the `/documents` bug in item 7c above. That check only works when the environment displays errors on-screen (the normal setting for local dev, not UAT/prod); see [`tests/playwright/README.md`](../tests/playwright/README.md) for detail on running this suite in an environment where it can actually catch this class of issue.

## Limitations of this automated pass

You've said you'll review everything manually anyway, so this is just a heads-up on where a browser script can't tell you what you actually need to know:

- **Item 4b (French Workbench text):** confirmed the text renders correctly and isn't corrupted, but this wording was newly authored during the upgrade (not recovered from an original source — see the UAT test plan for detail). Whether it's worth having someone fluent double-check the phrasing is your call — these are admin-only dashboard labels, so it may not be worth the time either way.
- **Item 10 (legacy list formatting):** confirmed both pages return 200 with no error, but automation didn't isolate a screenshot of the specific `<ol type="a">`-style list to confirm its visual styling — worth a look if that formatting detail matters to you.
- **Item 12 (two "growing-together" link values):** the post-update hook correctly identified these as ambiguous and left them alone rather than guess — someone needs to look at `/policy-benefits` and `/bid-value-reductions` and decide what the link should actually point to.
- Anything involving visual polish, subjective judgment, or content accuracy in general — the automated checks confirm things load, render without errors, and behave correctly structurally (buttons present/absent, fields present, status codes correct), not that everything *looks* right to a human eye.

## Playwright test suite

Saved to [`tests/playwright/`](../tests/playwright) (`tests/uat.spec.js`, `helpers.js`, `playwright.config.js`) so it can be re-run by anyone, with or without Claude Code:

```
cd tests/playwright
npm install
npx playwright install
npm test                    # runs both chromium and firefox, headed
```

`loginAs()` shells out to `ddev drush uli` for a fresh one-time login link on every call, so it must be run from a machine with ddev running for this project. Meant to be run locally against a dev environment with on-screen PHP error display enabled — see [`tests/playwright/README.md`](../tests/playwright/README.md) for why.

## Summary: safe to deploy to UAT?

**Yes — nothing found blocks deployment.** Everything that changed in this upgrade (patches kept, patches dropped, the workbench view fix, the link-field post-update hook, the FontAwesome icon-settings post-update hook, the `yukon_w3_custom` PHP warning fix, the removed permission) checks out correctly under automated testing in both browsers. The only follow-ups are non-blocking:

1. Optionally have someone fluent look over the newly-authored French Workbench dashboard text (item 4b) — up to you whether that's worth doing for an admin-only screen.
2. Manually determine the correct destination for the two "growing-together" links flagged by the post-update hook (item 12).
3. Optionally, a quick manual look at the two legacy-list-formatting pages (item 10) to visually confirm list styling.
