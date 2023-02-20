<?php

namespace Drupal\media_directories\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;

/**
 * Default plugin implementation of the Entity Reference Selection plugin.
 *
 * Also serves as a base class for specific types of Entity Reference
 * Selection plugins.
 *
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
 * @see \Drupal\Core\Entity\Annotation\EntityReferenceSelection
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
 * @see plugin_api
 *
 * @EntityReferenceSelection(
 *   id = "media_directory:default",
 *   label = @Translation("Media directory"),
 *   entity_types = {"taxonomy_term"},
 *   group = "default",
 *   weight = 0,
 * )
 */
class DirectorySelection extends TermSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    if ($match || $limit) {
      return parent::getReferenceableEntities($match, $match_operator, $limit);
    }

    $options = [];

    $bundles = $this->entityTypeBundleInfo->getBundleInfo('taxonomy_term');
    $bundle_names = $this->getConfiguration()['target_bundles'] ?: array_keys($bundles);

    $has_admin_access = $this->currentUser->hasPermission('administer taxonomy');
    $unpublished_terms = [];
    foreach ($bundle_names as $bundle) {
      if ($vocabulary = Vocabulary::load($bundle)) {
        /** @var \Drupal\taxonomy\TermInterface[] $terms */
        if ($terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary->id(), 0, NULL, TRUE)) {
          foreach ($terms as $term) {
            if (!$has_admin_access && (!$term->isPublished() || in_array($term->parent->target_id, $unpublished_terms))) {
              $unpublished_terms[] = $term->id();
              continue;
            }
            // Change default padding character and add an extra one.
            $options[$vocabulary->id()][$term->id()] = str_repeat('−', $term->depth + 1) . Html::escape($this->entityRepository->getTranslationFromContext($term)->label());
          }
        }
      }
    }

    return $options;
  }

}
