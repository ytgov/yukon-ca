TITLE: Kellett: Drupal to X.X.X, [update contrib,] [security advisories]

# This work includes

Update Drupal core and contrib.

# Deployment Instructions

<!-- Keep the summary and these instructions at the top; everything else goes below. If step 7's stale-vendor check failed, add `rm -rf vendor` before the `composer install` step. -->

1. Run `git pull`
2. **UAT ONLY:** deploy from the `staging` branch
3. Run `composer install --no-dev --prefer-dist --optimize-autoloader`\
   Review output to see that any patches apply successfully and the Drupal scaffold completes.
4. Run `drush deploy` (runs a cache rebuild, database updates, config import, and any
   `hook_deploy_NAME()` implementations, in the right order, followed by a final cache rebuild)
5. **PROD ONLY:** once the day's deployments are finished, check that the Unison sync between `cms-prd-web01` and `cms-prd-web02` is healthy by running `sudo tail -f /var/www/.unison/unison.log` on a prod web server. It should log entries every minute, each run ending with `Synchronization complete at HH:MM:SS  (N items transferred, 0 skipped, 0 failed)`. If you see errors or many `failed`, tell Lee O'Mara or Dave Rogers.

Upon completion, the site should load as expected. Review as anonymous, admin, and editor roles to ensure there are no obvious issues.

# Updates

Update Drupal core X.X.X => X.X.X.

Update contrib:

**Major version updates:**

- drupal/module_name (x.x.x => x.x.x)

**Minor version updates:**

- drupal/module_name (x.x.x => x.x.x)

**Removals:**

- drupal/module_name (x.x.x => x.x.x)

<details>
<summary>Full list of updates</summary>

- author/package-name (x.x.x => x.x.x)

</details>

<details>
<summary>Patches Removed or Updated</summary>

<!-- Any patch in composer.json's extra.patches that stopped applying, or whose source changed (e.g. rebased against a new MR), during this update. Note why, and link the issue/MR. Omit this section entirely if nothing changed. -->

- `drupal/module_name`: "patch description" — removed/updated because ...

</details>

<details>
<summary>Security Advisories Resolved</summary>

<!-- Run `composer audit` against the pre-update lock file and paste the results here verbatim, for every affected package, not just core. -->

```text
+-------------------+----------------------------------------------------------------------------------+
| Package           | drupal/core                                                                      |
| CVE               | CVE-XXXX-XXXXX                                                                   |
| Title             | Drupal core - ... - SA-CORE-XXXX-XXX                                             |
| URL               | https://www.drupal.org/sa-core-XXXX-XXX                                          |
| Affected versions | ...                                                                              |
| Reported at       | ...                                                                              |
+-------------------+----------------------------------------------------------------------------------+
```

</details>

# Known Issues

<!-- Genuine unresolved problems only. "None." if there aren't any. -->

None.

# Followup Tasks

<!-- Deliberate, already-decided work deferred to a later deploy for sequencing reasons, e.g. a package kept in composer.json on purpose pending a config change landing first. Keep separate from Known Issues. Omit this section entirely if there's nothing deferred. -->

- ...

# Notes

Updates were performed and tested locally under ddev.
