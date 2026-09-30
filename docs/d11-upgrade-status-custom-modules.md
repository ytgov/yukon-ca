# D11 Upgrade Status

## Summary

Overall: mostly minor/mechanical. One item is a genuine open question — 3 contrib modules with no published D11-compatible release, 1 of which (`tmgmt_diff`) is in active use and can't just be dropped.

| Severity | Item | Effort |
|---|---|---|
| **Major** | `advanced_help`, `ckeditor5_show_block`, `tmgmt_diff` have no D11-compatible release published anywhere (dev, alpha, or stable).<br><br>`advanced_help` and `ckeditor5_show_block` both look safe to remove (appear unused / low-value niche feature, see below).<br><br>`tmgmt_diff` is confirmed in active use with no upgrade path — needs ongoing upstream monitoring and, if no release lands in time, a fallback plan (patch, fork, or find a replacement) before D11. | Low for the two removals.<br><br>Assessment/monitoring for `tmgmt_diff`; real effort only if no upstream fix lands before D11. |
| Moderate | `webform`, `allowed_formats`, `media_directories`, `openid_connect` need a major-version bump to reach D11 compatibility (2 of the 4 only via a beta/alpha release).<br><br>Each is a real major bump — config/schema review needed, same as any other major contrib update. | Medium — one evaluated major bump per package |
| Minor | 7 of 9 custom modules/theme just need `^11` added to `core_version_requirement` in their `.info.yml`. No code changes. | Low — mechanical |
| Minor | `yukonca_glider` theme: one library reference (`desk_theme/swiper`) the scanner can't resolve — worth a quick check for staleness, independent of D11. | Low |
| Minor | `drupal/reroute_email`, `drupal/viewsreference` are uninstalled root composer requires — safe to remove outright. | Trivial |
| Minor | `drupal/ajax_comments`, `drupal/comment_notify` are pinned to dev branches but already D11-compatible — optional cleanup to a tagged release, not required for D11. | Trivial, optional |

## Safe to remove

Not installed (not in `core.extension.yml`), root composer requires with nothing else depending on them — remove from `composer.json` directly, no config uninstall needed:

- `drupal/reroute_email`
- `drupal/viewsreference`

## Contrib modules incompatible with D11 (Upgrade Status "Collaborate with maintainers")

This is the exact list the Upgrade Status UI's "Collaborate with maintainers" table shows as locally installed and D11-incompatible — not the dev-pin list below, which is a different (overlapping) set. Checked each against what's actually published on drupal.org/composer to work out the upgrade path: whether it's a single move (works on current D10 core *and* D11 already), needs a D10-only intermediate step before a separate D11-time move, or has no path published yet.

### Have an upgrade path

| Package | Currently installed | Path |
|---|---|---|
| `drupal/webform` | `6.2.x-dev` (== `6.2.11`, `^10.2`) | **Single step.** `6.3.0` (stable) supports `^10.3 \|\| ^11.0` — already covers current core (10.6.x) and D11 in one move. No D10-only intermediate needed.<br><br>It's a large, config-heavy module though, so evaluate the jump with the same care as any other major bump before taking it. |
| `drupal/allowed_formats` | `1.x-dev` (resolves like `^9.2 \|\| ^10`) | **Single step.** `3.0.1` supports `^10.1 \|\| ^11` — covers current core and D11 in one move, skipping the `2.0.0` (D10-only) release entirely. |
| `drupal/media_directories` | `2.1.x-dev` (`>=9.5`, no declared ceiling) | **Single step, but beta.** No stable release past `2.0.5` (which doesn't support D10 at all) exists in the 2.x line.<br><br>`3.0.0-beta1` supports `^10.2 \|\| ^11 \|\| ^12` — one move covers both, but it's still beta. Watch for it to reach stable, or accept the beta if D11 timing forces it. |
| `drupal/openid_connect` | `1.5.0` (already the latest 1.x tag, not dev-pinned — `^9.5 \|\| ^10`) | **Single step, but alpha — skip the 2.x line.** The `2.x` line (`2.0.0-beta3`: `^8.8 \|\| ^9`) is actually *older*-supporting than 1.5.0, not an upgrade path — looks like an abandoned/stale rewrite, don't use it as an intermediate.<br><br>`3.0.0-alpha8` supports `^10.2 \|\| ^11` directly from 1.x — one move, but still alpha. |

Summary: each of these 4 has a single-move upgrade path that clears D11 without a separate D10-only stepping-stone release — evaluate and take the major bump directly, either ahead of the D11 project or as part of it.

### No upgrade path published

| Package | Currently installed | Status |
|---|---|---|
| `drupal/advanced_help` | `1.x-dev` (`^8.8 \|\| ^9 \|\| ^10`) | **Blocked, and appears unused — recommend removal.** No release anywhere in this project — dev, alpha, stable — supports `^11` yet (latest is `1.1.0-alpha3`, same `^10` ceiling as current).<br><br>Checked usage: `composer why` shows only this repo's own root package requiring it; no custom module references it in code; no contrib module's `.info.yml` declares it as a dependency; its `help/` directory contains only the module's own built-in self-documentation, not real site content registered through it. Nothing on this site appears to actually use its help-topic system.<br><br>Recommend disabling and removing it rather than waiting on a D11 release — confirm in Drupal first at `/admin/help` (advanced_help replaces core's default help page) to make sure no genuinely useful topic is hiding there before removing. |
| `drupal/ckeditor5_show_block` | `1.14.0` (already the latest tag, not dev-pinned — `^9 \|\| ^10`) | **Blocked — recommend removal.** Only ever a `1.x` line; no newer major exists at all.<br><br>What it does: adds a CKEditor 5 toolbar button ("Show blocks") that outlines block-level HTML elements with their tag name while editing — an advanced authoring/debugging aid, wired into the `full_html` text format's toolbar (`ShowBlocks` in `config/default/editor.editor.full_html.yml`) and technically usable, but not the kind of feature most content editors would typically need or find useful day-to-day.<br><br>Given there's no D11 path and no wider dependency on it, recommend removing it rather than carrying an unmaintained-feeling module for a niche editing aid. To confirm current usage before removing: log in as a user with access to the Full HTML format, edit content using it, and check whether the "Show blocks" toolbar icon is something editors actually reach for. |
| `drupal/tmgmt_diff` | `1.0.0-alpha2` (already the latest release, not dev-pinned — `^9 \|\| ^10`) | **Blocked, but confirmed in active use — keep, watch upstream.** Only two releases ever published (`1.0.0-alpha1`, `1.0.0-alpha2`), no dev activity beyond that either.<br><br>What it does: on the Translation Management (TMGMT) Job Item edit/review form, highlights what changed between the previous and current machine/human translation, and can hide fields with no changes — meant to speed up reviewing re-translated content.<br><br>Confirmed active: `config/default/tmgmt_diff.settings.yml` has both its features (`hide_unchanged`, `diff_action`) turned on, and it hooks directly into the Job Item edit form (`tmgmt_diff_form_tmgmt_job_item_edit_form_alter()`).<br><br>To verify/see it: `/admin/tmgmt/jobs`, open an existing translation job's item for review/editing — fields with no change should be hidden, and changed fields should show a diff action near the preview. Settings live at `/admin/tmgmt/diff`. |

**Separate observation, not related to D11:** if `ckeditor5_show_block` is removed, worth having a genuine Filtered HTML text format available as an option instead of defaulting editors to Full HTML — a smaller, more restrictive format is generally better practice for content authoring. Independent recommendation, track separately from the D11 evaluation.

## Dev-pinned modules already D11-compatible (optional, not required for D11)

These 2 are pinned to `X.x-dev@dev` in `composer.json` but don't appear in the incompatible list above — their current dev-resolved code is already fine for D11. Not required for the D11 project; can be switched to a proper tagged release at the same time as other cleanup, whenever convenient:

| Package | Current pin | Tagged release available |
|---|---|---|
| `drupal/ajax_comments` | `1.x-dev` | `1.0.0-beta8` — `^10.2 \|\| ^11` |
| `drupal/comment_notify` | `1.x-dev` | `1.5.0` — `^9 \|\| ^10 \|\| ^11` |

**No security advisories exist against any of the dev-pinned or blocked packages above**, dev branch or any tagged release — confirmed by installing each package's tagged release line in isolation and running `composer audit --locked` against it. (`allowed_formats`'s scratch check surfaced 99 advisories, but every one was against `drupal/core` and its own transitive deps pulled in by the test's unconstrained core version — `drupal/allowed_formats` itself never appeared in the advisory list.)

**Why dev, and when** (for the packages pinned to `X.x-dev@dev`): each was pinned at the module's original install commit and never touched again — no later commit revisited the constraint, and none carry a stated reason beyond the install ticket:

| Package | Pinned in | Date |
|---|---|---|
| `advanced_help`, `ajax_comments` | `603cc7850` — "Installed all required modules" | 2023-01-02 |
| `allowed_formats`, `webform` | `1afd0c560` — "Initialized the Drupal Repo" | 2022-12-20 |
| `media_directories` | `43597bea1` — "Added, enabled and configured the media_directories module" | 2023-02-20 |
| `comment_notify` | `317f56118` — "OCT-1-2024" | 2024-10-01 |

Likely reason anyway, based on the version data above: at install time, the latest *tagged* release in that line didn't yet support the site's then-current core major, and only the dev branch did.

## Custom Module Updates

| Module | Findings |
|---|---|
| `goy_wildfire_low_bandwidth` | `core_version_requirement: ^10 \|\| ^9` needs `^11` added. |
| `yukon_base` | `core_version_requirement: ^9 \|\| ^10` needs `^11` added. |
| `yukon_department` | `core_version_requirement: ^9 \|\| ^10` needs `^11` added. |
| `yukon_hss_job_listings` | No known issues found. |
| `yukon_moderation` | `core_version_requirement: ^10` needs `^11` added. |
| `yukon_sso` | `core_version_requirement: ^9 \|\| ^10` needs `^11` added. |
| `yukon_taxonomy` | No known issues found. |
| `yukon_w3_custom` | `core_version_requirement: ^10 \|\| ^9` needs `^11` added. |
| `yukonca_glider` (theme) | `core_version_requirement: ^8 \|\| ^9 \|\| ^10` needs `^11` added.<br><br>Also: `pattern-carousel.html.twig:10` references the `desk_theme/swiper` library, which isn't an installed extension, so the scanner can't confirm whether it's deprecated — worth checking whether that's a stale/wrong reference independent of D11. |
