# yukon_moderation

Custom Drupal module providing content moderation enhancements for Yukon.ca, including a coordinated bilingual publication workflow, translation status management, and a cross-language revision comparison tool.

---

## Features

### 1. Content workflow

The site uses Drupal's `content_moderation` module with a single workflow named **Content** that applies to all node types. The workflow has seven states:

| State                               | Who uses it                                                   |
| ----------------------------------- | ------------------------------------------------------------- |
| `draft`                             | Initial/working state for any content                         |
| `needs_review`                      | Submitted for editorial review                                |
| `ready_to_publish`                  | Approved, ready for a publisher to release                    |
| `publish_as_scheduled`              | Queued for scheduled release                                  |
| `published`                         | Live on the site                                              |
| `ready_for_coordinated_publication` | French translation ready to go live when English is published |
| `archived`                          | Removed from public view                                      |

The `ready_for_coordinated_publication` state is only meaningful for French (non-default) translations. It is removed from the English node form via `hook_field_widget_complete_form_alter` and `hook_form_FORM_ID_alter` so English editors never see it.

Two additional form fields — `field_editor_publisher` and `field_publisher` — have conditional visibility tied to `needs_review` and `ready_to_publish` states respectively, managed in `hook_form_alter`.

### 2. Coordinated bilingual publication

**File:** `src/EventSubscriber/CoordinatedPublicationSubscriber.php`

The normal editorial workflow for bilingual content:

1. English editor creates/updates content and progresses it through review states to `published`.
2. While the English version is in review, the translator opens the French translation and sets it to `ready_for_coordinated_publication`.
3. When the English version is published, `CoordinatedPublicationSubscriber` fires and automatically publishes any French translation that is in `ready_for_coordinated_publication`.

The subscriber uses the State API (key prefix `yukon_moderation.coordinated_pending.<nid>.<langcode>`) to track which French revision was staged for coordinated publication. This is necessary because when the French translator saves and changes the moderation state, the English editor may later create additional English revisions — so the subscriber needs to know which French revision to publish.

The State API tracking is managed by `hook_entity_update` in `yukon_moderation.module`, which monitors `content_moderation_state` entities for non-default translations.

**Why the event subscriber, not a form hook?** The `workbench_email` module dispatches a `content_moderation.state_changed` event via its `hook_ENTITY_TYPE_update` for `content_moderation_state` entities. Because `workbench_email` moves its hook to the end of the invocation order, the State API tracking in `yukon_moderation_entity_update` is set before the event fires — so the subscriber always reads correct data.

**Note on the event shim:** `workbench_email` always dispatches the event with the **default (English)** node as the moderated entity, regardless of which translation triggered the state change. Code that subscribes to this event and needs to detect French translation publishes should use `hook_entity_update` on `content_moderation_state` entities instead (see §3 below).

### 3. Translation status management

**File:** `yukon_moderation.module` → `yukon_moderation_entity_update`

**Related:** `yukon_w3_custom` → `ContentTranslationController`, `TranslationStatuses`

Several content types have a `field_translation_status` field (a `list_string`) with two stored values: `in_progress` and `not_required`. The field is non-translatable — it exists only on the English (default) translation.

The content translation admin page at `/admin/content_translation` (provided by `yukon_w3_custom/ContentTranslationController`) shows a **Translation Status** column. The display logic is:

- If `field_translation_status` is `in_progress` or `not_required` → show that label.
- If the field is empty → compute the status dynamically:
  - French translation exists and French `changed` ≥ English `changed` → **Present**
  - French translation exists and French `changed` < English `changed` → **Out-dated**
  - No French translation → **Absent**

Editors set `field_translation_status` to `in_progress` when they send content to a translator. Once the French translation is published (either manually or via coordinated publication), `yukon_moderation_entity_update` detects the French `content_moderation_state` moving to `published` and clears `field_translation_status` on the English node. This causes the overview to switch automatically from "In-progress" to the computed "Present" status.

Because `field_translation_status` is non-translatable, clearing it requires loading and saving the English node, which creates one additional English revision. The revision log message records the action: *"French translation published — translation status automatically reverted."*

#### Content types with `field_translation_status`

```text
docroot/config/default/field.field.node.*.field_translation_status.yml
```

Run `find config/default -name "field.field.node.*.field_translation_status.yml"` to see the current list.

### 4. Cross-language revision comparison

**Files:** `src/Controller/NodeAllRevisionsController.php`, `src/Form/AllRevisionsComparisonForm.php`

An "All Revisions" tab is added to every node (route: `yukon_moderation.node_all_revisions`, path: `/node/{node}/all-revisions`). It lists all revisions across all languages in a single table, showing the revision date, author, log message, and moderation state.

Users can select any two revisions for comparison:

- **Same language** — delegates to the `diff` module's standard comparison view.
- **Cross-language** — uses a custom side-by-side table at `/node/{node}/compare-revisions/{left}/{right}/{left_lang}/{right_lang}`, which renders both translations field by field without attempting a text diff.

---

## Drush test commands

These commands verify the three main automated behaviours. Run them in sequence on a local DDEV environment. Each block creates a node, exercises the feature, checks the result, and cleans up.

### Test A — Translation status auto-clear on manual French publish

An editor sets `field_translation_status = in_progress`, a translator publishes the French translation directly. The field should be cleared.

```text
ddev drush php-eval '
$ns = \Drupal::entityTypeManager()->getStorage("node");

// Create English news node with in_progress status.
$node = $ns->create([
  "type" => "news",
  "title" => "[TEST A] Translation status auto-clear",
  "field_translation_status" => "in_progress",
  "moderation_state" => "published",
  "langcode" => "en",
]);
$node->save();
$nid = $node->id();
print "Created node $nid\n";

// Add and publish French translation directly.
$fr = $ns->load($nid)->addTranslation("fr", [
  "title" => "[TEST A] Effacement du statut de traduction",
  "moderation_state" => "published",
]);
$fr->save();
print "French translation published\n";

// Check result.
$default = $ns->load($nid);
$status = $default->get("field_translation_status")->getString();
print "field_translation_status: " . (empty($status) ? "(empty) PASS" : "$status FAIL") . "\n";

// Check revision log on latest English revision.
$vids = $ns->revisionIds($default);
$rev = $ns->loadRevision(end($vids));
print "Revision log: " . $rev->getRevisionLogMessage() . "\n";

// Cleanup.
$ns->load($nid)->delete();
print "Node $nid deleted.\n";
'
```

Expected output:

```text
Created node XXXXX
French translation published
field_translation_status: (empty) PASS
Revision log: French translation published — translation status automatically reverted.
Node XXXXX deleted.
```

---

### Test B — Coordinated publication (French auto-published when English is published)

A French translation is set to `ready_for_coordinated_publication`. When the English node is published, the French translation should be automatically published at the same time.

```text
ddev drush php-eval '
$ns = \Drupal::entityTypeManager()->getStorage("node");

// Create English node in draft.
$node = $ns->create([
  "type" => "news",
  "title" => "[TEST B] Coordinated publication",
  "moderation_state" => "draft",
  "langcode" => "en",
]);
$node->save();
$nid = $node->id();
print "Created English node $nid in draft\n";

// Add French translation in ready_for_coordinated_publication state.
$fr = $ns->load($nid)->addTranslation("fr", [
  "title" => "[TEST B] Publication coordonnée",
  "moderation_state" => "ready_for_coordinated_publication",
]);
$fr->save();
print "French set to ready_for_coordinated_publication\n";

// Publish English — should trigger French auto-publish.
$en = $ns->load($nid);
$en->set("moderation_state", "published");
$en->save();
print "English published\n";

// Check French moderation state.
$fr_check = $ns->load($nid)->getTranslation("fr");
$fr_state = $fr_check->get("moderation_state")->value;
print "French moderation_state: $fr_state " . ($fr_state === "published" ? "PASS" : "FAIL") . "\n";

// Cleanup.
$ns->load($nid)->delete();
print "Node $nid deleted.\n";
'
```

Expected output:

```text
Created English node XXXXX in draft
French set to ready_for_coordinated_publication
English published
French moderation_state: published PASS
Node XXXXX deleted.
```

---

### Test C — Translation status auto-clear on coordinated publication

Combines Tests A and B: `field_translation_status = in_progress` is set, French goes through coordinated publication. The field should be cleared when French is auto-published.

```text
ddev drush php-eval '
$ns = \Drupal::entityTypeManager()->getStorage("node");

// Create English node in draft with in_progress translation status.
$node = $ns->create([
  "type" => "news",
  "title" => "[TEST C] Coordinated pub + status clear",
  "field_translation_status" => "in_progress",
  "moderation_state" => "draft",
  "langcode" => "en",
]);
$node->save();
$nid = $node->id();
print "Created node $nid with field_translation_status=in_progress\n";

// Add French translation in ready_for_coordinated_publication state.
$fr = $ns->load($nid)->addTranslation("fr", [
  "title" => "[TEST C] Pub coordonnée + effacement",
  "moderation_state" => "ready_for_coordinated_publication",
]);
$fr->save();
print "French set to ready_for_coordinated_publication\n";

// Publish English.
$en = $ns->load($nid);
$en->set("moderation_state", "published");
$en->save();
print "English published\n";

// Check French was auto-published.
$fr_check = $ns->load($nid)->getTranslation("fr");
$fr_state = $fr_check->get("moderation_state")->value;
print "French moderation_state: $fr_state " . ($fr_state === "published" ? "PASS" : "FAIL") . "\n";

// Check translation status cleared.
$default = $ns->load($nid);
$status = $default->get("field_translation_status")->getString();
print "field_translation_status: " . (empty($status) ? "(empty) PASS" : "$status FAIL") . "\n";

// Cleanup.
$ns->load($nid)->delete();
print "Node $nid deleted.\n";
'
```

Expected output:

```text
Created node XXXXX with field_translation_status=in_progress
French set to ready_for_coordinated_publication
English published
French moderation_state: published PASS
field_translation_status: (empty) PASS
Node XXXXX deleted.
```

---

### Test D — `not_required` is also cleared when a French translation is published

A node marked as "not required" for translation already has a French translation added and published. The field should clear so the overview shows "Present" rather than the now-incorrect "Not-required".

```text
ddev drush php-eval '
$ns = \Drupal::entityTypeManager()->getStorage("node");

// Create English news node with not_required status.
$node = $ns->create([
  "type" => "news",
  "title" => "[TEST D] Not-required status clear",
  "field_translation_status" => "not_required",
  "moderation_state" => "published",
  "langcode" => "en",
]);
$node->save();
$nid = $node->id();
print "Created node $nid with field_translation_status=not_required\n";

// Add and publish French translation directly.
$fr = $ns->load($nid)->addTranslation("fr", [
  "title" => "[TEST D] Effacement du statut non requis",
  "moderation_state" => "published",
]);
$fr->save();
print "French translation published\n";

// Check result.
$default = $ns->load($nid);
$status = $default->get("field_translation_status")->getString();
print "field_translation_status: " . (empty($status) ? "(empty) PASS" : "$status FAIL") . "\n";

// Cleanup.
$ns->load($nid)->delete();
print "Node $nid deleted.\n";
'
```

Expected output:

```text
Created node XXXXX with field_translation_status=not_required
French translation published
field_translation_status: (empty) PASS
Node XXXXX deleted.
```

---

## Related modules

| Module                      | Relationship                                                                                                                                                                                                                                                                             |
| --------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `yukon_w3_custom`           | Provides `ContentTranslationController` (the `/admin/content_translation` overview page) and the `TranslationStatuses` trait which defines the five translation status labels. `yukon_moderation` clears `field_translation_status` so that controller computes "Present" automatically. |
| `content_moderation` (core) | Provides the `content_moderation_state` entity type and workflow engine. `yukon_moderation` hooks into `hook_entity_update` for `content_moderation_state` entities to detect translation state changes.                                                                                 |
| `workbench_email` (contrib) | Dispatches the `content_moderation.state_changed` event via a shim in `hook_ENTITY_TYPE_update`. The shim always uses the default (English) node as the moderated entity — this module's State API tracking and `hook_entity_update` work around that constraint.                        |
| `diff` (contrib)            | Used for same-language revision comparison in the All Revisions tab. Cross-language comparisons use a custom side-by-side view instead.                                                                                                                                                  |
