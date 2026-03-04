<?php

/**
 * @file
 * Drush script to archive news nodes in both English and French.
 *
 * Reads node IDs from news-node-ids-to-archive.csv (one ID per line) and sets the
 * moderation state to "archived" for each translation (en, fr).
 *
 * Note: This script bypasses workflow transition validation, so nodes in any
 * moderation state (draft, published, etc.) will be archived.
 *
 * Usage (from the Drupal docroot directory):
 *   drush php-script ../bulk/bulk-archive-news-nodes.php
 */

$csv_file = __DIR__ . '/news-node-ids-to-archive.csv';

if (!file_exists($csv_file)) {
  echo "ERROR: CSV file not found: $csv_file\n";
  exit(1);
}

$lines = array_filter(array_map('trim', file($csv_file)));

if (empty($lines)) {
  echo "ERROR: No node IDs found in CSV file.\n";
  exit(1);
}

$total = count($lines);
echo "Found $total node IDs. Starting archive process...\n\n";

$node_storage = \Drupal::entityTypeManager()->getStorage('node');
$languages = ['en', 'fr'];
$request_time = \Drupal::time()->getRequestTime();

$archived_count = 0;
$already_archived_count = 0;
$no_translation_count = 0;
$not_found_count = 0;
$error_count = 0;

foreach ($lines as $index => $line) {
  $nid = (int) $line;

  if (!$nid) {
    echo "  Line " . ($index + 1) . ": Invalid node ID '$line', skipping.\n";
    $not_found_count++;
    continue;
  }

  /** @var \Drupal\node\NodeInterface|null $node */
  $node = $node_storage->load($nid);

  if (!$node) {
    echo "  NID $nid: Node not found, skipping.\n";
    $not_found_count++;
    continue;
  }

  foreach ($languages as $langcode) {
    if (!$node->hasTranslation($langcode)) {
      echo "  NID $nid [$langcode]: No translation exists, skipping.\n";
      $no_translation_count++;
      continue;
    }

    $translation = $node->getTranslation($langcode);
    $current_state = $translation->get('moderation_state')->value;

    if ($current_state === 'archived') {
      echo "  NID $nid [$langcode]: Already archived.\n";
      $already_archived_count++;
      continue;
    }

    try {
      $translation->set('moderation_state', 'archived');
      $translation->setNewRevision(TRUE);
      $translation->setRevisionLogMessage('Bulk archived via drush script (bulk-news-archive-202603).');
      $translation->setRevisionCreationTime($request_time);
      $translation->setRevisionUserId(1);
      $translation->save();

      echo "  NID $nid [$langcode]: Archived (was: $current_state).\n";
      $archived_count++;
    }
    catch (\Exception $e) {
      echo "  NID $nid [$langcode]: ERROR - " . $e->getMessage() . "\n";
      $error_count++;
    }
  }

  // Print progress every 100 nodes.
  if (($index + 1) % 100 === 0) {
    $processed = $index + 1;
    echo "\n-- Progress: $processed / $total nodes processed --\n\n";
  }
}

echo "\n";
echo "=== Summary ===\n";
echo "Total node IDs in CSV:    $total\n";
echo "Translations archived:    $archived_count\n";
echo "Already archived:         $already_archived_count\n";
echo "Missing translations:     $no_translation_count\n";
echo "Nodes not found:          $not_found_count\n";
echo "Errors:                   $error_count\n";
