# yukon-ca

Drupal 10 site for Yukon.ca. Local dev via ddev (`ddev composer`, `ddev drush`, etc.).

## Layout

- Custom modules: `docroot/modules/custom/` (`yukon_base`, `yukon_department`, `yukon_hss_job_listings`, `yukon_moderation`, `yukon_sso`, `yukon_taxonomy`, `yukon_w3_custom`, `goy_wildfire_low_bandwidth`)
- Custom theme: `docroot/themes/custom/yukonca_glider`
- Drupal core is pinned to an exact version (not a caret range) in `composer.json` — deliberate, so `composer update` can't silently move core. See [docs/security-updates.md](docs/security-updates.md) for why and how to bump it.

## Branches

- `main` — production
- `staging` — UAT / test deployments

## Coding conventions

- All PHP code should conform to Drupal coding standards (see `phpcs`/`phpcs.xml` wrapper scripts in the repo). E.g. no fully-namespaced inline class references (`\Drupal\Core\Form\FormStateInterface`) — always add a `use` statement at the top of the file instead.
- FontAwesome is configured with `method: webfonts` (`config/default/fontawesome.settings.yml`), not `svg` — render icons as `<i class="fas fa-icon-name" aria-hidden="true"></i>`, never raw inline `<svg>` markup. Raw SVG icons silently lose their sizing CSS under this method (see the department-accordion icon regression).

## Pull requests

- Routine fixes/features: title `Kellett - refs #NNN: short description`, body with `## What's Included` and `## Deployment Steps` sections (see recent merged PRs for examples).
- Drupal core/contrib security updates: follow [docs/security-updates.md](docs/security-updates.md) and use [docs/security-update-pr-template.md](docs/security-update-pr-template.md) for the PR body — different format, includes full package update list and raw `composer audit` output.
