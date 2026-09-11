# D11 upgrade deployment process

## Why a pre-step is needed

Three contrib modules have no D11-compatible release and are being dropped entirely from the codebase as part of this upgrade (see [d11-upgrade-status-custom-modules.md](d11-upgrade-status-custom-modules.md)):

- `advanced_help`
- `ckeditor5_show_block`
- `tmgmt_diff`

All three are currently enabled in production (`config/default/core.extension.yml`). If the new codebase (which no longer contains their code, and whose `config/sync` no longer lists them as installed) is deployed first, `composer install` removes their code from `vendor`/`docroot/modules/contrib` while the site's active config still has them enabled — Drupal will hit a missing-extension error on the next request/`drush` command. Uninstalling them first, against the *currently deployed* code, avoids a two-phase deploy.

## Steps

Run against the **current (pre-upgrade) codebase**, before touching the deployment target's files:

1. Put the site in maintenance mode:
   ```bash
   drush sset system.maintenance_mode 1
   ```
2. Uninstall the three modules:
   ```bash
   drush pmu advanced_help ckeditor5_show_block tmgmt_diff -y
   ```
3. Confirm they're gone from active config:
   ```bash
   drush config:get core.extension | grep -E "advanced_help|ckeditor5_show_block|tmgmt_diff"
   ```
   (Expect no output.)

Then proceed with the deploy itself. Run these as separate steps rather than a single `drush deploy` — see the gotcha below step 7 for why:

4. Pull the D11 codebase.
5. `composer install --no-dev --prefer-dist --optimize-autoloader`
6. `drush updb -y` — runs pending database schema updates and this deployment's post-update hooks (the link-field and FontAwesome-icon-settings data backfills).
7. `drush cim -y` — imports configuration.

   **Known gotcha, confirmed while testing this process end-to-end against a from-scratch prod database:** on a large, from-scratch config import (many new config objects plus their French-language overrides all in one batch), Drupal core's config importer can fail partway with an error like:
   ```
   Update target "views.view.authmap" is missing.
   ```
   This is a batch-ordering artifact in Drupal core's `ConfigImporter` — a translation override gets synced before its own base config object finishes being created earlier in the same large batch — not a real config problem. **Just re-run `drush cim -y`.** The base object from the first attempt has already been created, so the retry only has the one remaining item left to apply, which goes through cleanly. Running `drush updb`/`drush cim` as separate steps (rather than bundled inside `drush deploy`) means a retry here doesn't have to repeat the update step.
8. `drush cr`
9. Take the site out of maintenance mode:
   ```bash
   drush sset system.maintenance_mode 0
   ```
10. Smoke-test.

## Rollback note

If the deploy needs to be aborted after step 2 but before the codebase is pulled, the site is left running the old core/contrib with those three modules uninstalled but not removed from disk — re-enabling them (`drush en <module> -y`) restores prior behaviour without needing a code rollback.
