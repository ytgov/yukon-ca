<?php

/**
 * @file
 * Post-deployment hooks, run by `drush deploy` after config import.
 */

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Normalize Call to action link values (refs #1055).
 *
 * Changes the default (published) revision of each paragraph and, when it is
 * different, the newest revision (an unpublished draft), so a draft doesn't
 * bring the old values back when it is published. Older revisions are left.
 * - No scheme ("example.com"): prefix https://.
 * - Site path, with or without "internal:" and with or without an /en or /fr
 *   prefix (the editor workaround for the broken entity: links): when it
 *   resolves to a node, store entity:node/NID so it survives alias changes.
 *   Query string and fragment are kept.
 * - Anything else (external URLs, entity:, unresolvable paths) is left alone;
 *   unresolvable site paths are listed in the output for manual follow-up.
 */
function yukon_base_deploy_1055_normalize_cta_links() {
  $alias_manager = \Drupal::service('path_alias.manager');
  $ids = \Drupal::entityQuery('paragraph')
    ->accessCheck(FALSE)
    ->condition('type', 'call_to_action')
    ->execute();

  $fixed = [];
  $manual = [];
  $storage = \Drupal::entityTypeManager()->getStorage('paragraph');
  $revisions = [];
  foreach (Paragraph::loadMultiple($ids) as $paragraph) {
    $revisions[] = $paragraph;
    $latest_id = $storage->getLatestRevisionId($paragraph->id());
    if ($latest_id && $latest_id != $paragraph->getRevisionId()) {
      $revisions[] = $storage->loadRevision($latest_id);
    }
  }

  foreach ($revisions as $paragraph) {
    if ($paragraph->get('field_link')->isEmpty()) {
      continue;
    }
    $old = $paragraph->get('field_link')->uri;
    $new = $old;

    if (preg_match('~^(?:internal:)?(/[^?#]*)([?#].*)?$~', $old, $m)) {
      $path = $m[1];
      $suffix = $m[2] ?? '';
      $langcode = $paragraph->language()->getId();
      if (preg_match('~^/(en|fr)(/.*)?$~', $path, $lang_match)) {
        $langcode = $lang_match[1];
        $path = $lang_match[2] ?: '/';
      }
      $system_path = $alias_manager->getPathByAlias($path, $langcode);
      if (preg_match('~^/node/(\d+)$~', $system_path, $node_match)) {
        $new = 'entity:node/' . $node_match[1] . $suffix;
      }
      elseif ($path !== '/') {
        $manual[$paragraph->id() . ' ' . $old] = $paragraph->id() . ' ' . $old;
      }
    }
    elseif (!preg_match('~^[a-z][a-z0-9+.-]*:~i', $old) && !preg_match('~^[#?]~', $old)) {
      $new = 'https://' . $old;
    }

    if ($new !== $old) {
      $paragraph->get('field_link')->uri = $new;
      $paragraph->setNewRevision(FALSE);
      // Don't bump "changed" or run translation side effects.
      $paragraph->setSyncing(TRUE);
      $paragraph->save();
      $fixed[] = $paragraph->id() . ($paragraph->isDefaultRevision() ? '' : ' (draft)');
    }
  }

  return 'Updated ' . count($fixed) . ' Call to action links. Needs manual fix (no matching page): ' . ($manual ? implode('; ', $manual) : 'none');
}
