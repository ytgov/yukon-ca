<?php

namespace Drupal\yukon_moderation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Displays a raw list of all revisions across all languages.
 */
class NodeAllRevisionsController extends ControllerBase {

  /**
   * Builds a raw revisions table.
   */
  public function viewAllRevisions(NodeInterface $node) {
    $header = [
      $this->t('Revision ID'),
      $this->t('Language'),
      $this->t('Date'),
      $this->t('User'),
      $this->t('Operations'),
    ];

    $rows = [];
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $revision_ids = $node_storage->revisionIds($node);
    $current_user = $this->currentUser();

    // Get the revision ID to avoid showing revert/delete for current revision.
    $current_revision_id = $node->getRevisionId();

    // Check permissions.
    $roles = $current_user->getRoles();
    $is_admin = in_array('administrator', $roles);
    $can_revert = $current_user->hasPermission('revert all revisions') ||
                  $current_user->hasPermission('administer nodes') ||
                  $is_admin;
    $can_delete = $current_user->hasPermission('delete all revisions') ||
                  $current_user->hasPermission('administer nodes') ||
                  $is_admin;

    foreach (array_reverse($revision_ids) as $vid) {
      $revision = $node_storage->loadRevision($vid);

      if (!$revision) {
        continue;
      }

      foreach ($revision->getTranslationLanguages() as $langcode => $language) {
        $translated_revision = $revision->getTranslation($langcode);

        if (!$translated_revision) {
          continue;
        }

        $user = $translated_revision->getRevisionUser();
        $username = $user ? $user->getDisplayName() : $this->t('Anonymous');
        $date = \Drupal::service('date.formatter')->format($translated_revision->getRevisionCreationTime(), 'short');
        $language_name = $language->getName() . ' (' . $langcode . ')';

        $operations = [];

        // View operation - use the same method as your original code.
        if ($translated_revision->hasLinkTemplate('revision')) {
          $operations['view'] = [
            'title' => $this->t('View'),
            'url' => $translated_revision->toUrl('revision'),
          ];
        }

        // Revert operation - don't show for current revision.
        if ($can_revert && $vid != $current_revision_id) {
          // Generate URL in the context of the target language.
          $url = Url::fromRoute('node.revision_revert_confirm', [
            'node' => $node->id(),
            'node_revision' => $vid,
          ], [
            'language' => $language,
          ]);

          $operations['revert'] = [
            'title' => $this->t('Revert'),
            'url' => $url,
          ];
        }

        // Delete operation - don't show for current revision.
        if ($can_delete && $vid != $current_revision_id) {
          // Generate URL in the context of the target language.
          $url = Url::fromRoute('node.revision_delete_confirm', [
            'node' => $node->id(),
            'node_revision' => $vid,
          ], [
            'language' => $language,
          ]);

          $operations['delete'] = [
            'title' => $this->t('Delete'),
            'url' => $url,
          ];
        }

        $rows[] = [
          $vid,
          $language_name,
          $date,
          $username,
          [
            'data' => [
              '#type' => 'operations',
              '#links' => $operations,
            ],
          ],
        ];
      }
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No revisions found.'),
    ];
  }

}
