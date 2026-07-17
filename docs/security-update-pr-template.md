TITLE: Kellett: Drupal to X.X.X, [update contrib,] [security advisories]

# This work includes

Update Drupal core and contrib.

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

<!-- Any patch in composer.json's extra.patches that stopped applying, or whose source changed (e.g. rebased against a new MR), during this update. Note why, and link the issue/MR. -->

- `drupal/module_name`: "patch description" — removed/updated because ...

</details>

<details>
<summary>Security Advisories Resolved</summary>

<!-- Run `composer audit` against the pre-update lock file and paste the results here verbatim. -->

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

None.

# Notes

Updates were performed and tested locally under ddev.

# Deployment Instructions

1. Run `git pull`
2. **UAT ONLY:** deploy from the `staging` branch
3. Run `composer install --no-dev --prefer-dist --optimize-autoloader`\
   Review output to see that any patches apply successfully and the Drupal scaffold completes.
4. Run `drush updb`
5. Run `drush cim` and see if it indicates any changes to import.
6. Run `drush cr` again if needed (may have been done after DB updates)

Upon completion, the site should load as expected. Review as anonymous, admin, and editor roles to ensure there are no obvious issues.
