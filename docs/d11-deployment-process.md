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

1. Sync the deploy target to the D11 codebase.

   **Known gotcha #0, confirmed on the real UAT deploy:** if the deploy target's checkout is behind and the remote branch's history has moved in a way that isn't a clean fast-forward (e.g. `staging` gets rebuilt from scratch/force-pushed whenever it needs to reflect a fresh combination of pending work, as happened preparing this upgrade), a plain `git pull` can hit a spurious merge conflict — specifically on `config/default/views.view.search.yml`, which has independently-edited exposed-filter settings from the search-view fix (issue #985 / PR #1148) — even though there's no real conflict to resolve, since the target's own history is simply stale, not diverged content that needs merging. **Use `git fetch origin && git reset --hard origin/<branch>` instead of `git pull`** (substituting the actual deploy branch, e.g. `staging` on UAT or `main` on prod). Not specific to `staging` or this upgrade — expect the same thing on any deploy target whose local checkout has drifted from the remote and touches this file, including `main` once #1148 and/or this branch deploy to prod.
2. `composer install --no-dev --prefer-dist --optimize-autoloader`

   **Known gotcha #1, confirmed both locally and on a real UAT deploy:** stale build artifacts left over from the previous codebase (`vendor`, `docroot/libraries`, and sometimes `docroot/core`/`docroot/modules/contrib`/`docroot/themes/contrib`/`docroot/profiles/contrib`) can confuse Composer's plugin/merge-file discovery, producing an error like:

   ```text
   Required package "..." is not present in the lock file.
   ```

   Delete `vendor` and `docroot/libraries/*` (safe — both are fully rebuilt by Composer) and re-run. If that's not enough, also delete `docroot/core`, `docroot/modules/contrib`, `docroot/themes/contrib`, and `docroot/profiles/contrib` — everything Composer manages, never `docroot/modules/custom` or `docroot/themes/custom`.

   **Known gotcha #2:** if it instead gets as far as `Running composer update to apply merge settings` and then fails with a network/TLS error (e.g. a curl SSL certificate error against `asset-packagist.org`) — that's `composer-merge-plugin` reconciling the `webform`/`media_directories` bundled library files, which needs to reach every repository declared in `composer.json`, not just this project's own packages. This is the deploy target's own network/certificate trust, not a code issue — retry once connectivity/trust is sorted, or have ops check that server's CA trust store.

   **Either way, verify the install actually completed rather than assuming it did:**

   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader --dry-run
   ```

   should report **"Nothing to install or update"** — if it lists anything pending, the previous run didn't fully finish and needs re-running. Worth also spot-checking that a couple of the merge-plugin-sourced libraries actually landed with files, not just an entry in the lock:

   ```bash
   ls docroot/libraries/choices docroot/libraries/jquery.chosen docroot/libraries/jquery.select2
   ```

3. `drush updb -y` — runs pending database schema updates and this deployment's post-update hooks (the link-field and FontAwesome-icon-settings data backfills).
4. `drush cim -y` — imports configuration.

   **Known gotcha #3, confirmed while testing this process end-to-end against a from-scratch prod database (and again on the real UAT deploy):** on a large, from-scratch config import (many new config objects plus their French-language overrides all in one batch), Drupal core's config importer can fail partway with an error like:

   ```text
   Update target "views.view.authmap" is missing.
   ```

   This is a batch-ordering artifact in Drupal core's `ConfigImporter` — a translation override gets synced before its own base config object finishes being created earlier in the same large batch — not a real config problem. **Just re-run `drush cim -y`.** The base object from the first attempt has already been created, so the retry only has the one remaining item left to apply, which goes through cleanly. Running `drush updb`/`drush cim` as separate steps (rather than bundled inside `drush deploy`) means a retry here doesn't have to repeat the update step.

   **You'll also likely see `[warning] Array to string conversion PdoTrait.php:109`** while the `workbench_current_user`/`workbench_edited`/`workbench_recent_content` views' French config imports — expected and harmless (a narrow `locale` module / `text_format` schema interaction, unrelated to this upgrade), not a sign of a failed or corrupted import.
5. `drush cr`
6. Take the site out of maintenance mode:

   ```bash
   drush sset system.maintenance_mode 0
   ```

7. Smoke-test.

## Rollback note

If the deploy needs to be aborted after step 2 but before the codebase is pulled, the site is left running the old core/contrib with those three modules uninstalled but not removed from disk — re-enabling them (`drush en <module> -y`) restores prior behaviour without needing a code rollback.
