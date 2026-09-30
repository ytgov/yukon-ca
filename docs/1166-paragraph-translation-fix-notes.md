# Notes: proper fix for translatable paragraph fields (issue #1166)

Working notes for revisiting the long-term fix once the immediate stopgap (installing
`paragraphs_asymmetric_translation_widgets`, see the issue comments) is in place and the D11
upgrade has shipped. This branch (`fix-paragraph-field-translation`) was reset to only carry the
stopgap — everything below is preserved here for whoever picks the proper fix back up.

## Root cause

`field_paragraphs` is set `translatable: true` on three node bundles and one paragraph bundle:

- `field.field.node.multi_step_page.field_paragraphs`
- `field.field.node.campaign_page.field_paragraphs`
- `field.field.node.topics_page.field_paragraphs`
- `field.field.paragraph.collapsable_field.field_paragraphs` (nested paragraph-in-paragraph case)

This means each language gets its own **independent set of paragraph entities**, instead of
sharing one set with per-paragraph translation. The fix is to set all four `translatable: false`,
plus a data-repair pass to reconcile the divergent per-language paragraph lists that already
exist before the config change takes effect (see below for why the order matters).

Two now-dead `drupal/paragraphs` patches can be dropped as part of this: `#2904705` ("Paragraph
translation", asymmetric-translation support — inert without the widgets module) and `#3125638`
("Paragraph translation 2", the local
`patches/paragraghs-buttons_do_not_appear_if_translated_node-3125638-15.patch`) — the button-gate
logic it touches is unreachable regardless, see the issue comments for the full trace.

## Scope, as last measured (D10 prod snapshot, 2026-09-14)

- **687** nodes total across the three bundles; **655** have both an EN and FR translation.
- **391** `collapsable_field` paragraph instances have both EN and FR translations.
- Of those **1046** host entities: **36 nodes** have a visibly different EN/FR paragraph *count*
  today (roughly split between EN-ahead and FR-ahead — not just "FR fell behind").
- Virtually all 1046 have **mismatched paragraph entity IDs** between languages even where counts
  match — i.e. two fully independent paragraph trees, not just occasionally diverged ones.
- 32 of the 36 count-mismatched nodes trace back to the Nov 2024 D7→D10 migration; 4 are native
  D10 content, confirming this is a live, ongoing bug and not just a historic migration artifact.

## The post-update hook (written, tested, reverted off this branch)

The full function set below (`yukon_base_post_update_reconcile_translatable_paragraph_fields()`
in `yukon_base.post_update.php`) was built and successfully run against the full snapshot. Save
this back into `docroot/modules/custom/yukon_base/yukon_base.post_update.php` to resume:

```php
<?php

/**
 * @file
 * Post update functions for Yukon Base.
 */

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Reconciles translatable paragraph-reference fields before they're made non-translatable.
 *
 * `field_paragraphs` was incorrectly set translatable on `multi_step_page`,
 * `campaign_page`, `topics_page` nodes and the `collapsable_field` paragraph
 * bundle (see issue #1166). That meant each language got its own
 * independent set of paragraph entities instead of sharing one set with
 * per-paragraph translations, so translating a page couldn't pick up steps
 * added on the other language's side.
 *
 * This must run BEFORE config import flips these fields to non-translatable
 * (i.e. via drush updb, which always runs before drush cim), while the
 * per-language field values set here are still read.
 *
 * For each host entity with both a source-language and other-language
 * translation, paragraphs are matched by position (and bundle type, as a
 * sanity check) between the two languages. Where a match has content only
 * on one side, that content is copied onto a translation of the shared
 * (matched) paragraph. Positions beyond the shorter list are kept as-is:
 * appended into the shared list, each remaining a paragraph whose own
 * language is whichever side had it, translation pending on the other
 * side. Nothing is deleted; mismatched bundle types at the same position
 * are left untouched and logged for manual review rather than guessed at.
 */
function yukon_base_post_update_reconcile_translatable_paragraph_fields(): void {
  $logger = \Drupal::logger('yukon_base');

  $targets = [
    ['entity_type' => 'node', 'bundle' => 'multi_step_page', 'field_name' => 'field_paragraphs'],
    ['entity_type' => 'node', 'bundle' => 'campaign_page', 'field_name' => 'field_paragraphs'],
    ['entity_type' => 'node', 'bundle' => 'topics_page', 'field_name' => 'field_paragraphs'],
    ['entity_type' => 'paragraph', 'bundle' => 'collapsable_field', 'field_name' => 'field_paragraphs'],
  ];

  $stats = [
    'hosts_processed' => 0,
    'translations_created' => 0,
    'already_translated' => 0,
    'source_field_updated' => 0,
    'ambiguous' => [],
  ];

  foreach ($targets as $target) {
    _yukon_base_reconcile_paragraph_field_for_bundle($target['entity_type'], $target['bundle'], $target['field_name'], $stats);
  }

  $logger->notice('Paragraph translation reconciliation: @hosts host entities processed, @created translation(s) created, @already already translated, @updated host field value(s) updated.', [
    '@hosts' => $stats['hosts_processed'],
    '@created' => $stats['translations_created'],
    '@already' => $stats['already_translated'],
    '@updated' => $stats['source_field_updated'],
  ]);

  if ($stats['ambiguous']) {
    $logger->warning('Skipped @count host entit(y/ies) with ambiguous paragraph structure (mismatched bundle type at the same position), needs manual review: @hosts', [
      '@count' => count($stats['ambiguous']),
      '@hosts' => implode('; ', $stats['ambiguous']),
    ]);
  }
}

/**
 * Reconciles one entity_type/bundle/field combination.
 */
function _yukon_base_reconcile_paragraph_field_for_bundle(string $entity_type_id, string $bundle, string $field_name, array &$stats): void {
  $entity_type_manager = \Drupal::entityTypeManager();
  $storage = $entity_type_manager->getStorage($entity_type_id);
  $bundle_key = $entity_type_manager->getDefinition($entity_type_id)->getKey('bundle');

  $ids = \Drupal::entityQuery($entity_type_id)
    ->condition($bundle_key, $bundle)
    ->accessCheck(FALSE)
    ->execute();

  foreach (array_chunk($ids, 50) as $chunk) {
    $storage->resetCache($chunk);
    foreach ($chunk as $id) {
      $host = $storage->load($id);
      if (!$host instanceof ContentEntityInterface || !$host->hasField($field_name)) {
        continue;
      }

      $source_langcode = $host->getUntranslated()->language()->getId();
      $other_langcodes = array_diff(array_keys($host->getTranslationLanguages()), [$source_langcode]);
      if (!$other_langcodes) {
        continue;
      }

      $stats['hosts_processed']++;
      foreach ($other_langcodes as $other_langcode) {
        _yukon_base_reconcile_paragraph_field($host, $field_name, $source_langcode, $other_langcode, $stats);
      }
    }
  }
}

/**
 * Reconciles a single host entity's paragraph field between two languages.
 */
function _yukon_base_reconcile_paragraph_field(ContentEntityInterface $host, string $field_name, string $source_langcode, string $other_langcode, array &$stats): void {
  $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');

  $source_host = $host->getTranslation($source_langcode);
  $other_host = $host->getTranslation($other_langcode);

  $source_paragraphs = [];
  foreach ($source_host->get($field_name) as $item) {
    $source_paragraphs[] = $paragraph_storage->load($item->target_id);
  }
  $other_paragraphs = [];
  foreach ($other_host->get($field_name) as $item) {
    $other_paragraphs[] = $paragraph_storage->load($item->target_id);
  }

  $matched_count = min(count($source_paragraphs), count($other_paragraphs));
  $final_paragraphs = [];
  $host_needs_save = FALSE;

  for ($i = 0; $i < $matched_count; $i++) {
    $source_paragraph = $source_paragraphs[$i];
    $other_paragraph = $other_paragraphs[$i];

    if (!$source_paragraph || !$other_paragraph || $source_paragraph->bundle() !== $other_paragraph->bundle()) {
      $stats['ambiguous'][] = $host->getEntityTypeId() . ':' . $host->id() . " ($field_name, position $i)";
      return;
    }

    if (!$source_paragraph->hasTranslation($other_langcode)) {
      $translation = $source_paragraph->addTranslation($other_langcode);
      foreach ($other_paragraph->getFieldDefinitions() as $sub_field_name => $definition) {
        // Only copy custom content fields: base/metadata fields (langcode,
        // default_langcode, content_moderation_state, etc.) must not be
        // copied onto a translation this way.
        if (str_starts_with($sub_field_name, 'field_') && $definition->isTranslatable() && $source_paragraph->hasField($sub_field_name)) {
          $translation->set($sub_field_name, $other_paragraph->get($sub_field_name)->getValue());
        }
      }
      $source_paragraph->setNewRevision(FALSE);
      $source_paragraph->save();
      $stats['translations_created']++;
    }
    else {
      $stats['already_translated']++;
    }

    $final_paragraphs[] = $source_paragraph;
  }

  // Extra source-only items stay as-is: nothing to translate yet, expected.
  for ($i = $matched_count; $i < count($source_paragraphs); $i++) {
    if ($source_paragraphs[$i]) {
      $final_paragraphs[] = $source_paragraphs[$i];
    }
  }

  // Extra other-language-only items are promoted into the shared list as-is
  // (their own language stays $other_langcode; $source_langcode translation
  // is pending, same as any other missing-translation content).
  for ($i = $matched_count; $i < count($other_paragraphs); $i++) {
    if ($other_paragraphs[$i]) {
      $final_paragraphs[] = $other_paragraphs[$i];
      $host_needs_save = TRUE;
    }
  }

  $final_values = array_map(fn($p) => [
    'target_id' => $p->id(),
    'target_revision_id' => $p->getRevisionId(),
  ], $final_paragraphs);

  // The other-language field value almost always needs realigning too: it
  // previously pointed at its own separate set of paragraphs, which now
  // needs to point at the same shared set as the source language.
  $final_ids = array_map(fn($p) => $p->id(), $final_paragraphs);
  $current_source_ids = array_map(fn($p) => $p ? $p->id() : NULL, $source_paragraphs);
  $current_other_ids = array_map(fn($p) => $p ? $p->id() : NULL, $other_paragraphs);
  if ($final_ids !== $current_source_ids || $final_ids !== $current_other_ids) {
    $host_needs_save = TRUE;
  }

  if ($host_needs_save) {
    $source_host->set($field_name, $final_values);
    $other_host->set($field_name, $final_values);
    $host->setNewRevision(FALSE);
    $host->save();
    $stats['source_field_updated']++;
  }
}
```

Last real run against the full snapshot: **1046 host entities processed, 2805 translations
created, 670 host field values updated, 10 flagged ambiguous** (bundle-type mismatch at a matched
position — logged for manual review, never guessed at). Config changes (the four `translatable:
false` edits) must be applied via `drush cim` *after* `drush updb` runs this hook — same order the
site's deployment process already uses, so no special-casing needed there.

### Bugs hit and fixed while building this (don't reintroduce)

1. **Never copy base/metadata fields onto a paragraph translation.** An early version looped over
   *all* translatable field definitions, including the `langcode` base field, and blindly copied
   its value — which throws `The translation language cannot be changed (fr)` when the two sides'
   `langcode` values don't already match. Fixed by restricting the copy to `field_*` (custom)
   fields only.
2. **Both host translations need their field value rewritten, not just the source.** An early
   version only triggered the final `$host->save()` when the *source* language's own list of IDs
   changed. Since the other language almost always points at a completely different (pre-fix) set
   of paragraph IDs, that condition needs to check both sides — otherwise the reconciled paragraph
   translations get created but the host's own field value on the non-source language is never
   realigned to use them. (In practice this doesn't cause visible harm once `translatable: false`
   ships, since Drupal then ignores the other-language field value entirely — but it leaves stale
   data at rest and should be fixed properly, not relied on being masked by the config change.)

## Known unresolved blocker: ~55 nodes stuck on a stale default revision

**This is the reason the fix wasn't finished and shipped.** 55 of the 1046 host entities (all
nodes, none of the 391 `collapsable_field` cases) never actually get their fix persisted when
processed through the normal Entity API save the hook above uses, even though no error is thrown
and no exception occurs.

**Root cause, confirmed:** these 55 nodes have a large gap between their current *default*
revision (what `Node::load()` and the live site actually serve) and their *latest* revision — in
one case (nid 3366) a gap of ~150,000 global revision IDs. Digging into
`content_moderation_state_field_revision`, these nodes have **independent per-language moderation
state** (e.g. EN sitting in `draft` while FR shows `published`), and `content_moderation`'s own
save-time logic decides whether a new revision becomes the default based on that — overriding
both `$entity->setNewRevision(FALSE)` and an explicit `$entity->isDefaultRevision(TRUE)` before
save. Any Entity-API save on these nodes just adds another non-default draft revision to the pile;
it never becomes what's actually served.

**Why this doesn't block the immediate stopgap:** confirmed all 55 are currently unpublished in
*both* languages on their default revision — checked every revision row (114 total) across all 55
nodes for `status = 1`: zero matches. None have ever been published, in any revision, in either
language. Not a live-content risk.

**What they actually are:** compiled a CSV (not checked into this repo — was handed to the client
directly) of all 55: title, created date, last-activity date, revision count, D7-migration origin.
53 of the 55 have exactly **one** revision, created during the Nov 2024 migration and never
touched since — clearly abandoned/orphaned migration artifacts (expired COVID-era campaign pages,
one-off surveys, orphaned utility sub-pages). The other 2 (nid 9254, 16516) show a second revision
dated recently, but tracing the actual revision metadata (`revision_log`, `revision_uid`,
`revision_timestamp`) shows those are byte-identical copies of the prior revision's metadata —
the signature of an automated bulk resave, not real editorial activity.

**Options for resolving this, not yet decided:**
1. Get the client's sign-off to simply delete these 55 nodes (strong case: never published, mostly
   single-revision migration artifacts nobody has touched in years) — removes the problem instead
   of solving it.
2. Match the established pattern already used by the other two post-update hooks in this file
   (`yukon_base_post_update_fix_schemeless_link_uris()`,
   `yukon_base_post_update_fix_null_fontawesome_icon_settings()`): write directly to the specific
   revision's rows via raw SQL (`UPDATE {node_revision__field_paragraphs} ... WHERE revision_id =
   :default_vid`) instead of going through the Entity API, bypassing `content_moderation`'s
   save-time revision logic entirely. More manual work to replicate the translation-creation
   logic (paragraph `addTranslation()` + field copy) in raw SQL, but it's the proven approach for
   exactly this kind of moderation/revision complication on this site.

## Other things worth knowing before resuming this

- **The hook only touches the current/default revision**, not full revision history. This differs
  from the other two post-update hooks in this file, which explicitly rewrite both the "current"
  table and the revision-history table. Historical revisions will keep showing the old, divergent
  per-language structure if anyone reverts to or diffs an old revision. Not fixed here — worth a
  deliberate decision on whether it's worth the effort, given the moderation-revision complexity
  above makes "just fix every revision" much harder than it sounds.
- **Orphaned paragraphs, once the config goes non-translatable and there's no data fix run first:**
  built and ran this exact scenario as a test. The old per-language-specific paragraphs are not
  deleted — they become unreferenced rows sitting in the database indefinitely once Drupal starts
  reading only the default translation's field value for every language. No errors, pages load
  fine, but old translated content for anything left unfixed silently becomes unreachable. Worth a
  follow-up cleanup pass (`entity:delete` on any paragraph with zero referencing fields) once the
  real fix ships, to avoid leaving that debris behind permanently.
- **`drush config:set` doesn't reliably persist `translatable` on a `field.field.*` config
  entity** in this environment — confirmed via raw `SELECT ... FROM {config}` that the CLI command
  reported success but never wrote the change. Using the Config API directly
  (`\Drupal::configFactory()->getEditable(...)->set(...)->save()`) worked correctly. **Not yet
  verified whether `drush cim`** (what the real deployment actually uses, not `config:set`) has
  the same problem — check this specifically before relying on the deploy process to apply the
  four config file changes.
- **Silent-relabel widget behavior, confirmed by building a real test case:** once a field is
  non-translatable, editing the *default*-language (EN) form for a paragraph whose only existing
  translation is the *other* language doesn't create a tracked translation the way the reverse
  direction does — it silently relabels that paragraph's own language to EN and shows the other
  language's text sitting in what's now an "EN" field, with no visual indicator it's still
  untranslated. Deliberately **not building a fix for this** (flagged to Lee, his call: "seems like
  extra work for a one-time thing — once those are fixed, that code will become a no-op"). Revisit
  only if he asks for it.
- **Playwright test for this fix should reuse the D11 upgrade branch's existing scaffold files
  verbatim** (`package.json`, `playwright.config.js`, `.gitignore`, `README.md`,
  `tests/helpers.js`) rather than recreating them from scratch — `tests/playwright/` doesn't exist
  on `main` at all, so two independently-authored scaffolds would produce an add/add conflict when
  this branch and the D11 branch eventually combine on `staging`. Copying the D11 branch's exact
  current versions of those files, then adding only a new spec file alongside `uat.spec.js`, keeps
  the merge clean. Not yet built.
