<?php

namespace Drupal\media_directories_ui;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Media directories UI helper service.
 */
class MediaDirectoriesUiHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The media directories settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Create an AdminToolbarToolsHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->settings = $config_factory->get('media_directories_ui.settings');
  }

  /**
   * Returns media type for specific file by mime type.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file of which the media type shall be guessed.
   *
   * @return \Drupal\media\MediaTypeInterface|null
   *   The media type if found, or NULL if not.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMediaType(FileInterface $file = NULL) {
    if ($file === NULL) {
      return NULL;
    }

    /** @var \Drupal\media\Entity\MediaType[] $types */
    $types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();

    $extension = pathinfo($file->getFileUri(), PATHINFO_EXTENSION);
    if (empty($extension)) {
      $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
    }
    $combined_media_types = ($this->settings->get('combined_upload_media_types') != NULL ? $this->settings->get('combined_upload_media_types') : []);

    foreach ($types as $type) {

      if (!in_array($type->id(), $combined_media_types, TRUE)) {
        continue;
      }

      $source_field = $type->getSource()->getConfiguration()['source_field'];
      $field_config = $this->entityTypeManager->getStorage('field_config')->load('media.' . $type->id() . '.' . $source_field);

      if (in_array($extension, explode(' ', $field_config->getSetting('file_extensions')))) {
        return $type;
      }
    }

    return NULL;
  }

  /**
   * Collect all supported extensions.
   *
   * @return string
   *   Valid file extensions separated by comma.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getValidExtensions() {
    $valid_extensions = [];
    $combined_media_types = ($this->settings->get('combined_upload_media_types') != NULL ? $this->settings->get('combined_upload_media_types') : []);
    /** @var \Drupal\media\Entity\MediaType[] $types */
    $types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();

    foreach ($types as $type) {

      if (!in_array($type->id(), $combined_media_types, TRUE)) {
        continue;
      }

      $source_field = $type->getSource()->getConfiguration()['source_field'];
      $field_config = $this->entityTypeManager->getStorage('field_config')->load('media.' . $type->id() . '.' . $source_field);
      $valid_extensions = array_merge($valid_extensions, explode(' ', $field_config->getSetting('file_extensions')));
    }

    $valid_extensions = array_unique($valid_extensions);

    return implode(' ', $valid_extensions);
  }

  /**
   * Checks if a term belongs to the specified anchestor.
   *
   * @param \Drupal\taxonomy\entity\Term $term
   *   The term to check against.
   * @param \Drupal\taxonomy\entity\Term $anchestor
   *   The anchestor to search for.
   *
   * @return boolean
   *   Wheater the provided anchestor is an anchestor.
   */
  public function termIsAnAnchestorOf(Term $term, Term $anchestor) {
    if ($term === NULL || $anchestor === NULL) {
      return FALSE;
    }

    /** @var \Drupal\taxonomy\Entity\Term[] $types */
    $anchestors = $this->entityTypeManager->getStorage('taxonomy_term')->loadAllParents($term->id());

    foreach ($anchestors as $one_of_the_anchestors) {
      if ($anchestor->id() == $one_of_the_anchestors->id()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Checks if a term belongs to the specified parent.
   *
   * @param \Drupal\taxonomy\entity\Term $term
   *   The term to check against.
   * @param \Drupal\taxonomy\entity\Term $parent|null
   *   The parent to search for, or NULL if the parent is <ROOT>.
   *
   * @return boolean
   *   Wheater the provided parent is a parent.
   */
  public function termIsAChildOf(Term $term, $parent) {
    if ($term === NULL) {
      return FALSE;
    }

    /** @var \Drupal\taxonomy\Entity\Term[] $types */
    $parents = $this->entityTypeManager->getStorage('taxonomy_term')->loadParents($term->id());
    if ($parent === NULL) {
      if (count($parents) == 0) {
        // See https://www.drupal.org/node/2019905
        return TRUE;
      }
      else {
        return FALSE;
      }
    }

    foreach ($parents as $one_of_the_parents) {
      if ($parent->id() == $one_of_the_parents->id()) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
