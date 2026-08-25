# Anchor navigation plan (issue #1104)

Issue: [Add anchors to the top of pages](https://github.com/ytgov/yukon-ca/issues/1104) — jump-link navigation for long pages, requested for Basic page, Campground and recreation site, Department, Places, and Multi-step page. Public engagement was removed from scope per client feedback (these are generally short pages that don't need anchor navigation).

## Current state

- Campaign Page already has a jump-point system (`navigation_jump_point` paragraph type + `field_navigation_jump_point`), but it's bespoke to that content type, manually curated by editors, and the anchor target is derived by stripping spaces from the link's own label text — no separate target-ID field was found, and no mechanism was found for placing a matching `id` next to a heading elsewhere on the page. Campaign Page is excluded from this issue (marked with a thumbs-down in the issue body) and is out of scope here.
- The theme also has an `anchor_links` pattern-library component (`patterns/organisms/anchor_links/`), but it is never invoked from any real node/paragraph template — it's a designed-but-unwired UI pattern. We are not trying to reuse or match this component's styling; the issue's screenshot example shows a different look.
- Both `drupal/table_of_contents` (Flexible Table Of Contents, ^2.1) and `drupal/anchor_link` (^3.0) are already in `composer.json`/`composer.lock` and enabled (added for the ddev proof-of-concept below) — but Flexible ToC isn't yet configured on any content type's view display.

The five in-scope content types split into two structurally different groups:

| Content type | Where the navigable content actually lives | Headings today |
|---|---|---|
| Basic page | `body` (single field) | editor-authored, tends to be `h2` |
| Multi-step page | `field_paragraphs` (rendered as tabs) — each tab is a `multi_step` paragraph with its own `field_section_content` field. `body` is only used as a short summary passed into the tabs UI, not the main content. | editor-authored, inside each paragraph's `field_section_content`, tends to be `h2` |
| Department | no body field | 0 real headings today — 2 field-group fieldsets (`fieldset`/`legend`, not heading tags) + 1 static hardcoded `<h3>Latest news</h3>` |
| Campground and recreation site | no body field (`field_site_description` is close but isn't the whole page) | 2 static hardcoded `<h3>` tags ("Location", "Services available") |
| Places (`directory_records_places`) | no body field | 0 headings of any kind |

## Approach: two tracks (plus a removed Track C, kept below for the record)

### Track A — `anchor_link` + Flexible Table Of Contents, for Basic page and Multi-step page

**Decision: pair `drupal/anchor_link` (manual anchor placement) with `drupal/table_of_contents`** (Flexible Table Of Contents, stable 2.1.0, D8–D11 — our core is pinned to 10.6.13, so compatible) configured with its CSS-selector setting pointed at `a.ck-anchor` rather than a heading tag.

`anchor_link` gives editors a CKEditor toolbar button that wraps selected text in `<a id="..." class="ck-anchor">` wherever they choose — confirmed working only in the `full_html` text format (the toolbar button and the unrestricted filter both live there), but that's not a practical gap: no content-editing role (`editor`, `writer`, `publisher`, etc.) has `use text format basic_html`/`restricted_html` — only `full_html` — so web reps already have access today.

Flexible ToC's per-field CSS selector isn't limited to heading tags — it's converted via Symfony's `CssSelectorConverter` to an XPath query and matched generically. Setting it to `a.ck-anchor` makes the generated ToC block reflect only the anchors editors deliberately placed, reusing the anchor's own `id` and wrapped text as the link target/label. This directly satisfies the "we don't want every H2 to automatically become an anchor" requirement from the web reps — Flexible ToC has no native per-heading include/exclude mechanism (confirmed by reading `TocTextFieldHelper`/`TextLongFieldTocBlock` source: the selector is all-or-nothing per field), so pairing it with `anchor_link` sidesteps that limitation entirely instead of patching the module or building custom exclusion logic.

Confirmed working end-to-end on a ddev proof-of-concept (`/en/another-test-page`): an editor-placed anchor rendered as `<a class="ck-anchor" id="anchor_1">...</a>`, and the ToC block generated a working `<a href="#anchor_1" class="toc-link">` pointing at it.

Chosen over **Content ToC** (`drupal/content_toc`, stable 1.0.4, D10.3+/D11) mainly on maturity and adoption: Flexible ToC reports 580 sites using it vs. Content ToC's 6, and a much longer issue-queue history (51 issues vs. 3) to gauge real-world edge cases and maintenance quality. Content ToC is also described as generating its outline "from... a node's body field" specifically, whereas Flexible ToC's configurable, selector-based, per-field approach is more likely to work cleanly when applied to `field_section_content` on the `multi_step` **paragraph** type below, not just a node's `body`.

Neither module's project page documents styling, sticky behavior, scroll-highlighting, or multilingual handling — this needs hands-on verification in ddev before implementation is considered done, especially given this site is bilingual (EN/FR) and the ToC label now comes from whatever text an editor wrapped in the anchor (translation of that wrapped text needs checking, not just translation of a template string).

**Basic page:** apply the formatter to the node's `body` field, selector `a.ck-anchor`.

**Multi-step page:** apply the formatter to `field_section_content` on the **`multi_step` paragraph type's own view display** (Manage Display for the paragraph bundle) — not the node's `body` field, since body is just a tab summary. This gives each tab its own scoped, in-tab TOC generated from that tab's own editor-placed anchors. Note this produces one TOC per tab, not one combined nav across the whole node — matches the tabbed UI shape rather than a single scrolling page.

**Confirmed, and this needs custom code (contradicts Track A's "no custom code" framing above):** enabling the TOC formatter on `field_section_content` derives a block plugin requiring an `entity:paragraph` context, constrained to bundle `multi_step`. Nothing in this site registers a context provider for `entity:paragraph` (verified directly: `context.repository`'s available contexts only cover `entity:node`, `entity:taxonomy_term`, `entity:user`, `entity:webform`, `entity:webform_submission`, plus `language` — no paragraph), so the block can never be placed through `/admin/structure/block`, confirmed by replicating Block Layout's own `getFilteredDefinitions('block_ui', ...)` filter directly and seeing it excluded.

Worse, this content type doesn't render its paragraphs through the normal entity pipeline at all: `node--multi-step-page.html.twig`'s tabs are built by `yukonca_glider_preprocess_node__multi_step_page()`, which runs each tab's raw field value through `check_markup()` directly — so even a `hook_preprocess_paragraph()`-based workaround has no effect (confirmed by testing; that was the first fix attempted here). The working fix, implemented and verified live on a real Multi-step node: inside `yukonca_glider_preprocess_node__multi_step_page()`, instantiate the derived block plugin per tab paragraph, call `setContextValue('entity', $paragraph)` to supply the context Block Layout can't, and prepend its rendered output to that tab's content string. Confirmed end-to-end on `/en/emergencies-and-safety/wildfires/prevent-and-prepare-wildfires` with a temporary test anchor (removed after verification).

Affected config/code:
- `config/default/core.entity_view_display.node.basic_page.*.yml`
- `config/default/core.entity_view_display.paragraph.multi_step.default.yml` (or a dedicated view mode, if one is introduced) — enables the TOC block deriver, but the block itself is never placed via Block Layout; see below.
- `docroot/themes/custom/yukonca_glider/yukonca_glider.theme` (`yukonca_glider_preprocess_node__multi_step_page()`) — custom code required to render the block, since Block Layout can't.

### Track B — bespoke fixed-section anchor list, for Department / Campground / Places

These content types' sections are template-structural, not editor-authored — a heading-scanning module has nothing variable to scan, and there's no rich-text field to place a manual `anchor_link` anchor in either. Instead, hard-code a small anchor-link list directly in each node template, with matching `id` attributes added next to each section's heading, conditional on whether that section actually has content to show.

**Status: not yet re-confirmed with the client.** Department's approach below was specified directly; Campground/Places still need their section boundaries decided; and the overall Track B approach hasn't been explicitly revisited since Track A's approach changed to the `anchor_link` pairing above.

**Department** (specified, with two corrections below):
- Convert `group_services_and_information` and the outer `group_minister_responsible` field-group format from `fieldset` to `html_element` (tag: `h2`) in `config/default/core.entity_view_display.node.department.default.yml` — **not** `department.full.yml`. `full.yml` has `status: false` (disabled); `default.yml` (`status: true`) is the config actually rendering the live page, even though the template selected is still `node--department--full.html.twig`. The two configs' `group_minister_responsible` have diverged: only the outer fieldset legend needs converting — its nested `group_row`/`group_minister_details_column` groups (live config only) are already `html_element` divs with `show_label: false`, so there's no second buried heading to convert there.
- Convert the static `<h3>Latest news</h3>` to `<h2>` in `node--department--full.html.twig`.
- `field_department_mandate` currently renders with **no heading at all** (`{{ content.field_department_mandate }}`, no wrapping tag) — unlike the other three sections, this one needs a *new* heading introduced in the twig, not just an `id` added to something that already exists.
- Check `field_add_branch` — it renders after `group_minister_responsible` (an entity-reference-revisions view of some paragraph type) as a 5th content block not counted in the "up to 4 sections" framing below. Needs checking for whether it renders its own heading that should also be a candidate anchor.
- Add `id` attributes to each section's heading (mandate, services and information, minister responsible, latest news — plus `field_add_branch` if it turns out to need one).
- Add the anchor-nav markup itself to `node--department--full.html.twig`.
- Restyle `docroot/themes/custom/yukonca_glider/src/sass/pages/_department.page.scss` — the existing `fieldset.fieldset-title` / `legend` rules (lines 68–79) won't apply to `<h2>` and need replacing. The current fieldset legend is explicitly pinned to `text-[22px] font-medium`, smaller than the theme's default `h2` (`text-3xl font-medium`, see `_typhography.scss:14-16`) — the new `<h2>` markup needs a scoped override (dedicated class, applied only to these three Department headings) to keep the rendered size at 22px rather than inheriting the default h2 size. Add a short comment in the SCSS noting why the override exists, so it isn't mistaken for stale/removable code later.

**Campground and recreation site** (not yet specified — open item below, and more involved than "same pattern" implies):
- Only one of the two headings is actually hardcoded in the node twig: "Location" is a literal `<h3>` in `node--campground-directory-record--full.html.twig:172`. "Services available" is not — it comes from the shared pattern component `patterns/organisms/icon_cards/pattern-icon-cards.html.twig:10` (`<h3>{{ title|t }}</h3>`), invoked with `title: 'Services available'`. Since `icon_cards` is a shared organism, hardcoding an `id` directly in the pattern template risks a collision if it's ever invoked more than once on a page or reused elsewhere — the `id` needs to be passed in as a parameter from the caller instead.
- A third, previously unaccounted-for section exists: `pattern('graph_card', {title: 'Campground peak times:', graph: availability})` (twig lines 153–162), shown only when `field_campground_site_type` isn't one of a few excluded values. With only `title`+`graph` passed, `graph_card`'s template renders "Campground peak times:" as a plain `<div class="title field__label mb-4">`, not a heading — same no-heading gap as Department's mandate field, but in a second shared pattern component, and conditional on data (won't always be present).
- Net: this content type needs the boundaries/heading text decision (open item below) plus changes across two shared pattern components, not just the node template.

**Places** (not yet specified — open item below, plus one structural constraint):
- `node--directory-records-places.html.twig` has **no view-mode suffix** and contains no `{% if page %}`/`view_mode` branching anywhere — it's the single template used for every view mode of this bundle (teaser, listings, full page alike). Any new heading/anchor-nav markup added here needs to be wrapped in a `page`-conditional or it will leak into teaser/listing contexts (search results, related-content teasers), not just the full page.
- The `group_row`/`group_details` field-groups defined in `core.entity_view_display.node.directory_records_places.default.yml` are configured but unused — the twig prints fields individually (`content.field_street_address`, etc.) rather than the group wrapper, so this isn't existing structure to build the anchor sections on top of.
- Zero headings of any kind today (confirmed) — section headings need introducing from scratch, guarded by the `page` check above.

Affected files (Department confirmed; Campground/Places shape now scoped, section boundaries still TBD):
- `config/default/core.entity_view_display.node.department.default.yml`
- `docroot/themes/custom/yukonca_glider/templates/content/node--department--full.html.twig`
- `docroot/themes/custom/yukonca_glider/src/sass/pages/_department.page.scss`
- `docroot/themes/custom/yukonca_glider/templates/content/node--campground-directory-record--full.html.twig` + related SCSS
- `docroot/themes/custom/yukonca_glider/patterns/organisms/icon_cards/pattern-icon-cards.html.twig` (needs an `id` parameter added)
- `docroot/themes/custom/yukonca_glider/patterns/molecules/graph_card/pattern-graph-card.html.twig` (needs a heading + `id` parameter added, if this section is in scope)
- `docroot/themes/custom/yukonca_glider/templates/content/node--directory-records-places.html.twig` (needs `page`-conditional guarding added) + related SCSS

### Track C — removed from scope

Public Engagement was dropped from the issue's scope per client feedback: these are generally short pages that don't need anchor navigation. The multi-field problem described in earlier drafts of this plan (7 separate `text_long` fields, no single field to point a TOC formatter at) no longer needs solving.

## Rejected alternative: TOC Node module

`toc_node` scans the entire rendered node (all visible fields, via DOMDocument) rather than one field, which would otherwise be the better architectural fit for Department/Public Engagement. Rejected because it has **no Drupal 9, 10, or 11 release at all** — not even a dev branch (releases stop at `7.x-1.7` and an `8.x-1.x-dev` branch, both marked Unsupported). Not viable on this site's D10 core regardless of maintenance status.

`toc_field` and `ptoc` (Paragraphs Table of Contents) were not fully vetted against D10/D11 compatibility — no longer worth chasing further now that Track C (the multi-field problem they'd have solved) is out of scope.

## Return to top button (sitewide, independent of Tracks A/B)

**Decision: use `drupal/back_to_top`** (^3.0, `core_version_requirement: ^9 || ^10 || ^11`) over `drupal/scroll_top_button` (^2.1.1, `^8.8 || ^9 || ^10 || ^11`).

Comparison:
- **Adoption:** `back_to_top` ~22,075 sites vs. `scroll_top_button` ~1,242 sites.
- **Accessibility (the deciding factor for a government site):** confirmed by reading each module's button-rendering JS directly. `back_to_top` builds `<nav aria-label="Back to top"><button id="backtotop" aria-label="Back to top">...</button></nav>` — a real, labelled `<button>`. `scroll_top_button` renders a bare `<a href="#">` with no `aria-label` — flagged in its own open issue queue ("Link is not accessible", citing WebAIM guidance against href="#"-only links); a fix has a merge request open (March 2026) but isn't in a tagged release yet.
- **Known issue, accepted:** `back_to_top`'s `js/back_to_top.js` assigns `speed = settings.back_to_top.back_to_top_speed;` inside its click handler without `var`/`let`/`const` — an implicit global that throws `ReferenceError: speed is not defined` under strict-mode JS execution, matching an open, unresolved issue against 3.0.3. Not hit on a prior site using this module; treating as a known, monitored risk rather than a blocker.

Scope of work:
- Add `drupal/back_to_top` to `composer.json`/`composer.lock`, enable the module.
- Configure via its settings form: button text, scroll-trigger threshold, animation speed, and the mobile/admin/front-page visibility toggles.
- Style pass against `yukonca_glider` — the module ships default colors (`back_to_top_bg_color`, `_border_color`, `_hover_color`, `_text_color`) that will need overriding to match brand rather than left at module defaults.
- Verify the `speed` bug doesn't manifest in this site's actual JS aggregation/strict-mode setup before considering this done.

Affected files:
- `composer.json` / `composer.lock`
- `config/default/back_to_top.settings.yml` (new config export)
- theme SCSS for the button's colors/positioning (`yukonca_glider`, exact file TBD once a design pass happens)

## Trade-offs

- Two different mechanisms across the five content types means more code paths to maintain long-term, but each matches what its content type actually supports — forcing one module everywhere would either miss content or require inventing headings/fields that don't exist.
- `drupal/table_of_contents` and `drupal/anchor_link` are both new dependencies and enter the normal contrib module security-tracking process going forward; ditto `drupal/back_to_top` for the return-to-top feature.
- Track A now depends on editors remembering to manually place anchors — nothing generates them automatically. That's the point (web reps explicitly didn't want every heading auto-included), but it means an editor who forgets to add an anchor gets no ToC entry at all, with no warning.
- The Department/Campground/Places nav is hand-maintained: it won't auto-update if a section is added, removed, or reordered later. Low risk in practice since these templates rarely restructure, but worth knowing.
- Multi-step page's per-tab TOC means a visitor sees a fresh nav when switching tabs rather than one nav covering the whole node — matches the tabbed UI, but is a different mental model than the other content types' single top-of-page nav.
- Multi-step page needs custom code after all (`yukonca_glider_preprocess_node__multi_step_page()`), not just module configuration — the derived block requires an `entity:paragraph` context Block Layout can't supply, confirmed by directly testing Drupal's own block-placement filter. Basic page has no such issue (node context is available natively).
- `back_to_top`'s known `speed` bug (see above) is an accepted risk, not a resolved one — worth a quick regression check after go-live.

## Open items before implementation

1. Decide Campground and Places section boundaries/heading text (Department's naming was specified directly; these two were not).
2. Confirm the overall Track B approach with the client — it hasn't been explicitly re-confirmed since Track A's approach changed to the `anchor_link` pairing.
3. Match visual design to the issue's screenshot example / the Kellett reference implementation — styling wasn't scoped in this plan and needs a design pass across Track A, B, and the return-to-top button.
4. Verify Flexible Table Of Contents' styling, sticky/collapsible behavior, scroll-highlighting, and multilingual (EN/FR) handling hands-on in ddev — none of this is documented on the project page, and the ToC label now depends on editor-entered anchor text rather than a fixed heading. (The block-placement mechanics themselves are now resolved for both Basic page and Multi-step page — see Track A above.)
5. Configure and style `back_to_top`, and confirm the `speed` bug doesn't surface in this site's setup (see Return to top button section).
