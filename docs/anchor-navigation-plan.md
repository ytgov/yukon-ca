# Anchor navigation plan (issue #1104)

Issue: [Add anchors to the top of pages](https://github.com/ytgov/yukon-ca/issues/1104) — jump-link navigation for long pages, requested for Basic page, Campground and recreation site, Department, Places, Public engagements, and Multi-step page.

## Current state

- Campaign Page already has a jump-point system (`navigation_jump_point` paragraph type + `field_navigation_jump_point`), but it's bespoke to that content type, manually curated by editors, and the anchor target is derived by stripping spaces from the link's own label text — no separate target-ID field was found, and no mechanism was found for placing a matching `id` next to a heading elsewhere on the page. Campaign Page is excluded from this issue (marked with a thumbs-down in the issue body) and is out of scope here.
- The theme also has an `anchor_links` pattern-library component (`patterns/organisms/anchor_links/`), but it is never invoked from any real node/paragraph template — it's a designed-but-unwired UI pattern. We are not trying to reuse or match this component's styling; the issue's screenshot example shows a different look.
- Neither Flexible Table Of Contents nor any other TOC-related module is currently in `composer.json`/`composer.lock`.

The six in-scope content types split into three structurally different groups:

| Content type | Where the navigable content actually lives | Headings today |
|---|---|---|
| Basic page | `body` (single field) | editor-authored, tends to be `h2` |
| Multi-step page | `field_paragraphs` (rendered as tabs) — each tab is a `multi_step` paragraph with its own `field_section_content` field. `body` is only used as a short summary passed into the tabs UI, not the main content. | editor-authored, inside each paragraph's `field_section_content`, tends to be `h2` |
| Public engagements (`engagement`) | Spread across up to 7 separate `text_long` fields: `body` **+** `field_engagement_find_info`, `_participate`, `_results`, `_feedback`, `_info_use`, `_find_results` — no single field holds "the page" | editor-authored, potentially inside any/all of the 7 fields |
| Department | no body field | 0 real headings today — 2 field-group fieldsets (`fieldset`/`legend`, not heading tags) + 1 static hardcoded `<h3>Latest news</h3>` |
| Campground and recreation site | no body field (`field_site_description` is close but isn't the whole page) | 2 static hardcoded `<h3>` tags ("Location", "Services available") |
| Places (`directory_records_places`) | no body field | 0 headings of any kind |

## Approach: three tracks

### Track A — contrib TOC module (single-field formatter), for Basic page and Multi-step page

Two viable candidates, both D10-compatible and actively maintained, both working as a field formatter on **one field**:

- **Flexible Table Of Contents** (`drupal/table_of_contents`) — stable 2.1.0, D8–D11. Enabled per bundle via Manage Display, with a configurable CSS selector (e.g. `h2`, or `h2, h3`) for which elements become TOC entries.
- **Content ToC** (`drupal/content_toc`) — stable 1.0.4 (March 2026), D10.3+/D11. Per its project description, automatically generates an outline "typically from the headings h2, h3 etc. inside a node's body field."

**Which of the two to use is still an open decision** — see open items below; this plan doesn't yet compare their admin UI/config depth or output styling in enough detail to pick one.

**Basic page:** apply the formatter to the node's `body` field. Heading level is confirmed as `h2` in practice.

**Multi-step page:** apply the formatter to `field_section_content` on the **`multi_step` paragraph type's own view display** (Manage Display for the paragraph bundle) — not the node's `body` field, since body is just a tab summary. This gives each tab its own scoped, in-tab TOC generated from that tab's own headings (confirmed to tend toward `h2`). Note this produces one TOC per tab, not one combined nav across the whole node — matches the tabbed UI shape rather than a single scrolling page.

Affected config:
- `composer.json` / `composer.lock`
- `config/default/core.entity_view_display.node.basic_page.*.yml`
- `config/default/core.entity_view_display.paragraph.multi_step.default.yml` (or a dedicated view mode, if one is introduced)

### Track B — bespoke fixed-section anchor list, for Department / Campground / Places

These content types' sections are template-structural, not editor-authored — a heading-scanning module has nothing variable to scan. Instead, hard-code a small anchor-link list directly in each node template, with matching `id` attributes added next to each section's heading, conditional on whether that section actually has content to show.

**Department** (specified):
- Convert `group_services_and_information` and `group_minister_responsible` field-group format from `fieldset` to `html_element` (tag: `h2`) in `config/default/core.entity_view_display.node.department.full.yml`.
- Convert the static `<h3>Latest news</h3>` to `<h2>` in `node--department--full.html.twig`.
- Add `id` attributes to each of the (up to) 4 sections: mandate, services and information, minister responsible, latest news.
- Add the anchor-nav markup itself to `node--department--full.html.twig`.
- Restyle `docroot/themes/custom/yukonca_glider/src/sass/pages/_department.page.scss` — the existing `fieldset.fieldset-title` / `legend` rules (lines 68–79) won't apply to `<h2>` and need replacing. The current fieldset legend is explicitly pinned to `text-[22px] font-medium`, smaller than the theme's default `h2` (`text-3xl font-medium`, see `_typhography.scss:14-16`) — the new `<h2>` markup needs a scoped override (dedicated class, applied only to these three Department headings) to keep the rendered size at 22px rather than inheriting the default h2 size. Add a short comment in the SCSS noting why the override exists, so it isn't mistaken for stale/removable code later.

**Campground and recreation site / Places** (not yet specified — open item below): same pattern (add real `<h2>` headings + matching IDs + hard-coded nav list), but the exact section boundaries and heading text still need to be decided. Places currently has zero headings, so this also means introducing section headings where none exist today.

Affected files (Department confirmed; Campground/Places same shape, TBD specifics):
- `config/default/core.entity_view_display.node.department.full.yml`
- `docroot/themes/custom/yukonca_glider/templates/content/node--department--full.html.twig`
- `docroot/themes/custom/yukonca_glider/src/sass/pages/_department.page.scss`
- `docroot/themes/custom/yukonca_glider/templates/content/node--campground-directory-record--full.html.twig` + related SCSS
- `docroot/themes/custom/yukonca_glider/templates/content/node--directory-records-places.html.twig` + related SCSS

### Track C — unresolved: Public Engagement's multi-field editor content

Public Engagement doesn't fit either track cleanly: unlike Department/Campground/Places, its 7 candidate fields hold genuinely variable, editor-authored rich text (not fixed template labels), so Track B's hard-coded-anchor-list approach doesn't apply as-is. But unlike Basic page/Multi-step, there's no single field to point a Track A formatter at, and combining headings from all 7 fields into one nav is exactly what the (rejected, D10-incompatible) TOC Node module was built to do.

Options, none chosen yet:
1. Apply the Track A formatter separately to each of the 7 fields, producing up to 7 small stacked TOC blocks rather than one combined nav at the top of the page. Cheapest, but doesn't match the issue's "one TOC at the top" reference design.
2. Build a small custom preprocess for this content type only, that concatenates the rendered output of all 7 fields and scans for headings (effectively a scoped, hand-rolled version of what `toc_node` does) — gives the "one combined nav at the top" shape the issue wants, at the cost of custom code to maintain.
3. Re-vet `toc_field` or `ptoc` (Paragraphs Table of Contents) in case either can span multiple fields on one entity — neither has been checked against this specific need yet.

This needs a decision before implementation can start on Public Engagement.

## Rejected alternative: TOC Node module

`toc_node` scans the entire rendered node (all visible fields, via DOMDocument) rather than one field, which would otherwise be the better architectural fit for Department/Public Engagement. Rejected because it has **no Drupal 9, 10, or 11 release at all** — not even a dev branch (releases stop at `7.x-1.7` and an `8.x-1.x-dev` branch, both marked Unsupported). Not viable on this site's D10 core regardless of maintenance status.

`toc_field` and `ptoc` (Paragraphs Table of Contents) were not fully vetted — worth checking against D10-compatibility and the Track C multi-field problem above.

## Trade-offs

- Three different mechanisms across the six content types means more code paths to maintain long-term, but each matches what its content type actually supports — forcing one module everywhere would either miss content or require inventing headings/fields that don't exist.
- `drupal/table_of_contents` (or `drupal/content_toc`) is a new dependency and enters the normal contrib module security-tracking process going forward.
- The Department/Campground/Places nav is hand-maintained: it won't auto-update if a section is added, removed, or reordered later. Low risk in practice since these templates rarely restructure, but worth knowing.
- Multi-step page's per-tab TOC means a visitor sees a fresh nav when switching tabs rather than one nav covering the whole node — matches the tabbed UI, but is a different mental model than the other content types' single top-of-page nav.
- Public Engagement (Track C) is the least resolved part of this plan — whichever option is chosen, it's either weaker UX (stacked per-field TOCs) or new custom code to maintain (bespoke multi-field scan).

## Open items before implementation

1. Decide Campground and Places section boundaries/heading text (Department's naming was specified directly; these two were not).
2. Match visual design to the issue's screenshot example / the Kellett reference implementation — styling wasn't scoped in this plan and needs a design pass across Track A, B, and C.
3. Choose between Flexible Table Of Contents and Content ToC for Track A (config depth, output styling, ease of matching the issue's screenshot design).
4. Resolve Track C for Public Engagement (see three options above).
