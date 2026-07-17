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

## 4. Write the PR

Use [security-update-pr-template.md](security-update-pr-template.md). Paste `composer audit` output verbatim — don't summarize or reformat it.
