<?php

/**
 * @file
 * Post update functions for Yukon Base.
 */

/**
 * Fixes link field values that are missing a URI scheme.
 *
 * These values were written directly to the database by a legacy import,
 * bypassing the Link widget's validation, which would otherwise reject a
 * bare domain or path with no scheme. Left as-is, some of these crash on
 * render (see issue #1143 for the mining-investment-yukon example).
 */
function yukon_base_post_update_fix_schemeless_link_uris(): void {
  $connection = \Drupal::database();
  $logger = \Drupal::logger('yukon_base');

  $tables = [
    'node__field_website' => 'field_website_uri',
    'node__field_related_link' => 'field_related_link_uri',
    'node__field_top_task' => 'field_top_task_uri',
    'node__field_link_title' => 'field_link_title_uri',
    'node__field_related_tasks' => 'field_related_tasks_uri',
    'node__field_statutory_holiday_link' => 'field_statutory_holiday_link_uri',
    'node__field_campaign_home_logo_link' => 'field_campaign_home_logo_link_uri',
    'paragraph__field_link' => 'field_link_uri',
    'paragraph__field_link_image' => 'field_link_image_uri',
  ];

  $valid_scheme_pattern = '/^(https?|internal|entity|route|base|public|private|mailto|tel):/';
  $bare_domain_pattern = '/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+(\/.*)?$/i';

  $fixed = 0;
  $skipped = [];

  foreach ($tables as $table => $column) {
    $target_tables = [$table];
    $revision_table = str_replace('__', '_revision__', $table);
    if ($connection->schema()->tableExists($revision_table)) {
      // Scanned and fixed independently: a paragraph's default-revision
      // ("current") table and its own revision-history table can disagree
      // for the same entity_id/revision_id pair, so a bad value in one
      // doesn't guarantee a matching bad value in the other (see #1143).
      $target_tables[] = $revision_table;
    }

    foreach ($target_tables as $target_table) {
      if (!$connection->schema()->tableExists($target_table)) {
        continue;
      }

      $rows = $connection->select($target_table, 't')
        ->fields('t', ['entity_id', 'revision_id', $column])
        ->execute();

      foreach ($rows as $row) {
        $uri = $row->{$column};
        if ($uri === NULL || $uri === '' || preg_match($valid_scheme_pattern, $uri)) {
          continue;
        }

        if (str_starts_with($uri, '/') || str_starts_with($uri, '?') || str_starts_with($uri, '#')) {
          $corrected = 'internal:' . $uri;
        }
        elseif (preg_match($bare_domain_pattern, $uri)) {
          $corrected = 'https://' . $uri;
        }
        else {
          $skipped[] = "$target_table.$column (entity {$row->entity_id}, revision {$row->revision_id}): $uri";
          continue;
        }

        $connection->update($target_table)
          ->fields([$column => $corrected])
          ->condition('entity_id', $row->entity_id)
          ->condition('revision_id', $row->revision_id)
          ->execute();

        $fixed++;
      }
    }
  }

  $logger->notice('Fixed @fixed schemeless link URI value(s).', ['@fixed' => $fixed]);
  if ($skipped) {
    $logger->warning('Skipped @count ambiguous link URI value(s), needs manual review: @values', [
      '@count' => count($skipped),
      '@values' => implode('; ', $skipped),
    ]);
  }
}
