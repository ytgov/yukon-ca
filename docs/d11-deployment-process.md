# D11 upgrade deployment process

## Why a pre-step is needed

Three contrib modules have no D11-compatible release and are being dropped entirely from the codebase as part of this upgrade (see [d11-upgrade-status-custom-modules.md](d11-upgrade-status-custom-modules.md)):

- `advanced_help`
- `ckeditor5_show_block`
- `tmgmt_diff`

All three are currently enabled in production (`config/default/core.extension.yml`). If the new codebase (which no longer contains their code, and whose `config/sync` no longer lists them as installed) is deployed first, `composer install` removes their code from `vendor`/`docroot/modules/contrib` while the site's active config still has them enabled — Drupal will hit a missing-extension error on the next request/`drush` command. Uninstalling them first, against the *currently deployed* code, avoids a two-phase deploy.

## Steps

Run against the **current (pre-upgrade) codebase**, before touching the deployment target's files:

1. Put the site in maintenance mode.
2. Uninstall the three modules:
   ```bash
   drush pmu advanced_help ckeditor5_show_block tmgmt_diff -y
   ```
3. Confirm they're gone from active config:
   ```bash
   drush config:get core.extension | grep -E "advanced_help|ckeditor5_show_block|tmgmt_diff"
   ```
   (Expect no output.)

Then proceed with the normal deploy:

4. Pull the D11 codebase.
5. `composer install`
6. `drush deploy` (or the site's equivalent: `updb`, `cim`, `cr`)
7. Take the site out of maintenance mode.
8. Smoke-test.

## Rollback note

If the deploy needs to be aborted after step 2 but before the codebase is pulled, the site is left running the old core/contrib with those three modules uninstalled but not removed from disk — re-enabling them (`drush en <module> -y`) restores prior behaviour without needing a code rollback.
