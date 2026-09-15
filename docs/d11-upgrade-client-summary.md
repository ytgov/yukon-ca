# Drupal 11 Upgrade — Summary for Yukon.ca

A plain-language summary of the Drupal 11 upgrade for Yukon.ca. Full technical detail lives in [issue #1143](https://github.com/ytgov/yukon-ca/issues/1143) and [PR #1165](https://github.com/ytgov/yukon-ca/pull/1165) on GitHub — this document is the short version.

## How it was tested

A large part of this project was building confidence that the upgrade is safe to deploy:

- **Full deployment rehearsals.** We reset a local copy of the site back to match production exactly — same code, same database — then ran through the *actual* deployment steps we'll use on UAT and production. This caught several real gotchas (things like a harmless-but-alarming-looking error on config import, and a git conflict trap on deploy) that we then documented so the real deployment goes smoothly, with no surprises.
- **Automated browser testing.** We built a suite of automated tests (using a tool called Playwright) that click through the site the way a real visitor, editor, or translator would — logging in, creating content, checking translations, submitting forms, and so on. This suite ships with the codebase, so anyone with a local copy of the site can re-run it themselves for extra verification.
- **Every automated test run was watched by a person**, not just left to run unattended. That means someone was checking not only "did the expected thing happen," but also watching for anything else unusual — warnings or errors popping up that had nothing to do with the upgrade itself, in case they pointed at a pre-existing problem worth fixing.
- **Manual UAT checklists** were also prepared (as requested), broken out by role (content editors, translators, general public), so non-technical reviewers can do their own spot-checks without needing to understand the technical side.

## Decisions made along the way (within scope)

A few small things came up during testing that weren't strictly part of the upgrade, but were quick, safe fixes — so we included them rather than opening separate tickets and deployments for each:

- **Cleaned up outdated code patches.** Over time, sites accumulate small patches to work around bugs in Drupal or its add-on modules. As part of this upgrade we reviewed every one of them and dropped the ones no longer needed (fixed upstream, or the underlying issue simply doesn't apply anymore), keeping only what's still genuinely required.
- **Fixed a handful of pre-existing bugs found during testing**, unrelated to the upgrade itself but surfaced by it — a page that could crash on bad legacy data, a warning message on the documents page, and some corrupted dashboard text (English and French) in an admin-only screen. All quick, low-risk fixes.
- **Removed a newly auto-granted permission.** Drupal 11 automatically grants a "rebuild content access permissions" permission to any role that already had broader "administer content" access — six editorial roles picked this up automatically during the upgrade. Drupal itself flags this permission as disruptive, and none of those roles need it, so we removed it. (Confirmed with you already — no action needed.)
- **Left one harmless warning message alone.** Saving a couple of admin dashboard views triggers a cosmetic PHP warning in the site's log. It doesn't affect anything and isn't something specific to this site to fix — noting it so it doesn't cause concern if someone spots it in the logs later.

## Recommendations that need a decision from you

These came up during the work but are genuinely optional, and outside the scope of the upgrade itself. None of them block deployment.

- **Old test credentials sitting in the codebase.** While updating one of the login modules, we noticed some old development server login details (not production credentials) stored in plain text in a config file that's visible in the public repo history. Not a live security risk as far as we know, but worth deciding whether to leave it, remove it going forward, or have it scrubbed from the project's history entirely.
- **A workaround in the site's theme code that blocks direct access to "department" pages.** This already has its own tracking issue ([#1163](https://github.com/ytgov/yukon-ca/issues/1163)) — flagging it here just so it's on your radar for prioritizing.
- **A broader code review of the site's theme and custom modules.** A few small quality issues turned up in the theme code while testing this upgrade (including the item above). None are urgent, but there may be value in a dedicated review pass to catch anything else similar, when time allows.
- **A general review of roles and permissions.** Not urgent — you've already indicated this can wait until after the existing permissions issues (#955/#956) are addressed, but it's worth keeping on the list.
- **French text sanity check.** Two admin-only dashboard labels had corrupted text that we had to rewrite from scratch (no original version to recover). It's not public-facing, but a fluent French speaker double-checking the wording at some point wouldn't hurt.

## Where things stand

The upgrade is deployed to UAT and ready for your review. No known issues are blocking a production deployment once you're satisfied with testing.
