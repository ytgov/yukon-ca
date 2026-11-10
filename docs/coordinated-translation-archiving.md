# Coordinated translation archiving — implementation notes

Context: [#1066 - When Eng pages archived, Fre side is not automatically archived](https://github.com/ytgov/yukon-ca/issues/1066).

Content moderation treats each translation's moderation state independently by
design — this is expected Drupal behaviour, not a bug (see
[Using Content Moderation with Translation](https://www.drupal.org/docs/8/core/modules/content-moderation/using-content-moderation-with-translation)).
The client wants archiving the default (English) translation to also archive
other published translations. This note records the technical details for
whoever implements it, so the revision-safety consideration below isn't lost.

## Where this belongs

Extend `docroot/modules/custom/yukon_moderation/src/EventSubscriber/CoordinatedPublicationSubscriber.php`
(or add a sibling listener in the same module) to also react when the default
translation's new state is `archived`, cascading to non-default translations
that are currently `published`.

## Revision model (why the naive approach is risky)

- A node's revisions are **not** per-language. Each revision row is a full
  snapshot of the entity across all translations. There is one shared
  revision-ID sequence for English and French together.
- Whether a save promotes its revision to "current/default" is decided by
  `content_moderation`'s `EntityOperations::entityPresave()`
  (`docroot/core/modules/content_moderation/src/EntityOperations.php:118-120`),
  based on whether the *state being saved* is a `default_revision: true`
  state in the workflow (`published` and `archived` both are, per
  `config/default/workflows.workflow.content.yml`). This applies to the
  whole revision, not just the language being saved.
- Each translation separately tracks whether it was actually changed in a
  given revision via the `revision_translation_affected` flag
  (`isRevisionTranslationAffected()`). Core resolves "the latest revision
  that actually touched this language" via
  `TranslatableRevisionableStorageInterface::getLatestTranslationAffectedRevisionId($id, $langcode)`
  (used in `docroot/core/modules/content_moderation/src/ModerationInformation.php:125`).
  The standard node edit form uses this to make sure an editor always starts
  from the correct base revision per language, even when another language has
  a newer pending revision in between.

**The risk:** if the new archive-cascade code loads the node with a plain
`$node_storage->load($nid)` (default revision only) instead of resolving each
translation's own latest revision via `getLatestTranslationAffectedRevisionId()`,
and a translation has a newer, not-yet-promoted forward/pending revision at
the moment the cascade runs, the cascade's save would be built from a stale
snapshot of that language — bypassing the pending edit rather than building on
top of it. Nothing is deleted (Drupal never removes prior revisions), but the
cascade could produce a new "current" revision for that language that doesn't
reflect its most recent pending changes.

**Fix:** when loading each translation to archive, resolve its latest
revision via `getLatestTranslationAffectedRevisionId()` (same as core's own
edit form does) rather than relying on the default-revision entity object,
before setting `moderation_state` to `archived` and saving.

## Bypassing the declared transition

`workflows.workflow.content.yml` declares the `archived` transition as only
valid `from: published`. This is a form/UI-level constraint, not something
enforced when a translation's `moderation_state` field is set directly and
saved — `CoordinatedPublicationSubscriber` already does this for `published`
(`$translation->set('moderation_state', 'published'); $translation->save();`,
line 89-90) without going through the transition object. The archive-cascade
can do the same regardless of the translation's current state — no revision
or content is lost by doing so, since Drupal retains all prior revisions
regardless of the current moderation state.

## Scope decided

Cascade should archive all non-default translations, published or not (e.g.
a French translation sitting in `draft`/`needs_review` when English is
archived also gets archived). This is safe per the point above: archiving a
non-published translation doesn't discard its in-progress revision, it just
changes what's "current."

## Role/permission context

CONFIRMED (`config/default/user.role.*.yml`): `publisher` has `use content
transition archived` (the permission that lets someone move content into the
`archived` state) but **no `translate <bundle> node` permissions at all**.
`editor`/`writer` have neither. Only `translator`, `administrator`, and
`site_administrator` hold both the translate permissions needed to edit a
French translation and `use content transition archived`.

This means the role that normally archives English (`publisher`) is
structurally unable to also archive French — it's not just a missed step,
the permission model prevents it. This is why the issue's documented
workaround exists (web rep alerts the FrenchTranslation team, who manually
archive). Worth citing if the client asks why this needs code rather than a
process reminder.

## Finding existing orphans (EN archived, FR still published)

`content_moderation_state_field_data` stores each translation's current
moderation state per `(content_entity_id, langcode)`. Orphans are found with
a self-join:

```sql
SELECT en.content_entity_id
FROM content_moderation_state_field_data en
JOIN content_moderation_state_field_data fr
  ON en.content_entity_id = fr.content_entity_id
 AND en.content_entity_type_id = fr.content_entity_type_id
WHERE en.langcode = 'en' AND en.moderation_state = 'archived'
  AND fr.langcode = 'fr' AND fr.moderation_state = 'published'
```

### Admin UI: extend `/admin/content_translation`

`yukon_w3_custom/src/Controller/ContentTranslationController.php` already
implements a `translation_status` computed filter (`absent`/`present`/
`out_dated`, lines 146-176) using the same kind of join, against
`node_field_data`. Add an `orphaned` branch using the query above (against
`content_moderation_state_field_data` instead) as one more `elseif` — reuses
the existing filter form, pager, and table rendering. This is simpler than a
Views-based report: Views has no clean built-in way to compare two
languages' moderation states on the same row without a custom
relationship/join plugin, which would end up more work than extending code
that already does the equivalent join.

Optionally also add EN/FR moderation-state columns to the table (not just
the filter) so mismatches are visible without filtering — generalizes to
catch other odd EN/FR combinations beyond this specific case, for free.

### Bulk-fixing existing orphans on deploy (update hook)

**Recommendation: filter addition only, for now.** The admin UI filter is
needed regardless of whether the update hook happens (it's the general-purpose
way to find orphans, not just a one-time cleanup tool), and is the lower-effort
piece. The update hook is optional extra scope — leaving it out keeps the
initial implementation smaller. The details below are kept in case the client
decides they want the deploy-time bulk fix instead of (or as well as) manually
triaging via the filter.

Alongside the cascade code, add a `hook_post_update_NAME()` in
`yukon_moderation.post_update.php` that runs the orphan query above once and
archives each match. Extract the actual "archive this translation safely"
logic (latest-per-language revision lookup, direct `moderation_state` field
set, bypassing the transition object — see above) into a shared method used
by both the event subscriber and the update hook, so the logic isn't
duplicated. `hook_post_update` (not `hook_update_N`) because it runs after
config import with full entity API available, which this needs.

This doesn't replace the admin UI filter above — keep both:

- The update hook is a one-shot fix for production data at deploy time; it
  needs to be right the first time and there's no built-in way to verify its
  result afterward except re-running the same query.
- The filter is the ongoing safety net for anything that creates a new
  orphan outside the normal editorial path (migration, direct DB fix, future
  bug) and doubles as the way to confirm the update hook actually caught
  everything.

## Testing to cover

- English archived while French is published → French archived.
- English archived while French has a pending/forward revision (not yet
  promoted to default) that hasn't been saved through the normal edit form →
  confirm the cascade doesn't silently drop that pending revision's changes.
- English archived while French is already in a non-published, non-live
  state → confirm archiving still succeeds and revision history remains
  intact.

## Why not Rules or ECA

- Neither module has a built-in event for "moderation state changed" — this
  is an open, unresolved gap in Drupal core itself
  ([core issue #2873287](https://www.drupal.org/project/drupal/issues/2873287)).
  Both would need the same kind of workaround this codebase already uses.
- `yukon_moderation` already consumes the one purpose-built shim for this
  (`workbench_email`'s `ContentModerationStateChangedEvent`), via
  `CoordinatedPublicationSubscriber`.
- Adding a full rules-engine module for one cascade rule is more overhead
  (admin UI, its own upgrade/security surface) than extending code already
  running in production.
