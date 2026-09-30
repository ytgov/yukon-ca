# Security update process

Core and contrib updates are done as separate, isolated commits — not one big `composer update`. This keeps patch failures and dependency conflicts traceable to a single package, and keeps the exact-pinned core version (see `composer.json`) from drifting on unrelated contrib work.

## 1. Capture the "before" advisory baseline

Before changing anything, snapshot what `composer audit` reports against the *current* lock file, so the PR can show exactly which advisories the update resolves:

```bash
mkdir .tmp-audit-before
git show HEAD:composer.lock > .tmp-audit-before/composer.lock
git show HEAD:composer.json > .tmp-audit-before/composer.json
ddev exec -- composer audit --working-dir=/var/www/html/.tmp-audit-before --locked
rm -rf .tmp-audit-before
```

Keep the `drupal/core` entries from this output — they go in the PR.

## 2. Update core on its own

Bump the exact pinned version in `composer.json` for all three:

```text
"drupal/core-composer-scaffold": "X.Y.Z",
"drupal/core-project-message": "X.Y.Z",
"drupal/core-recommended": "X.Y.Z",
```

Find the latest version with `ddev composer show -a drupal/core-recommended` (look at the `versions` line). Stay on the current major unless a major upgrade is explicitly planned.

Then update only those packages (this also pulls in core's own dependency bumps — Symfony components, Guzzle, Twig, etc. — but nothing else):

```bash
ddev composer update drupal/core-recommended drupal/core-composer-scaffold drupal/core-project-message --with-all-dependencies
```

Check the output for patch failures — all patches under `extra.patches."drupal/core"` in `composer.json` must still apply.

## 3. Check what's left

```bash
ddev composer audit
```

Anything remaining is a separate package. Each gets its own commit/update, one advisory (or package) at a time — don't bundle unrelated contrib bumps together.

## 4. Update everything else

Once core and any advisory-affected packages are handled, run a full `composer update` to bring every other package current too — not just the ones with an open advisory. Capture the full list of installs/updates/removals from the output for the PR.

A full update can remove a package that's still in active use if it was only present as another package's transitive dependency (not in this project's own `composer.json` `require`) and the resolver decides nothing hard-requires it anymore. If the site fails to boot afterward with an `Extension` assertion error for a missing module, check whether that module is genuinely still used (enabled in `core.extension.yml`, referenced by a field type, etc.) and if so, add it directly to `composer.json`'s `require` — don't rely on transitive resolution for anything the site actually depends on.

## 5. Handle patch failures

A full update can break an existing patch in `extra.patches` if the target package restructures its code (e.g. a module refactoring away from procedural `.module`/`.inc` files into OOP hook classes) — the patch failing doesn't necessarily mean the underlying bug is fixed or gone. Before patching around it:

1. Reproduce the actual bug on this site first (find where the affected code path is really exercised here — a token used in a webform, a view, etc.) — don't assume the patch is still needed just because it's in `composer.json`.
2. Check the patch's issue queue on drupal.org for a newer patch/MR that targets the current code structure. If one doesn't exist, fork the project's git repo, port the fix to its new location, and open a new MR referencing the original issue.
3. Point `composer.json`'s patch entry at the new MR's `.diff` URL and re-run `composer install` to confirm it applies cleanly (check `vendor/composer/installed.json`'s `extra.patches_applied` for the package to be sure).
4. Note the change in the PR's "Patches Removed or Updated" section (see template) — this needs to be visible to reviewers since it's not a normal version bump.

## 6. Write the PR

Use [security-update-pr-template.md](security-update-pr-template.md). Paste `composer audit` output verbatim — don't summarize or reformat it.
