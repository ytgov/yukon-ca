<?php

namespace Drupal\cshs;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;

/**
 * The taxonomy storages.
 */
trait TaxonomyStorages {

  /**
   * An instance of the "entity.repository" service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|null
   */
  protected ?EntityRepositoryInterface $entityRepository = NULL;

  /**
   * An instance of the "entity_type.manager" service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected ?EntityTypeManagerInterface $entityTypeManager = NULL;

  /**
   * The state of whether content translation should be used.
   *
   * @var bool|null
   */
  private ?bool $needsTranslatedContent = NULL;

  /**
   * Returns storage of the "taxonomy_term" entities.
   *
   * @return \Drupal\taxonomy\TermStorageInterface
   *   The storage of the "taxonomy_term" entities.
   */
  protected function getTermStorage(): TermStorageInterface {
    return $this->getStorage('taxonomy_term');
  }

  /**
   * Returns storage of the "taxonomy_vocabulary" entities.
   *
   * @return \Drupal\taxonomy\VocabularyStorageInterface
   *   The storage of the "taxonomy_vocabulary" entities.
   */
  protected function getVocabularyStorage(): VocabularyStorageInterface {
    return $this->getStorage('taxonomy_vocabulary');
  }

  /**
   * Returns the entity translation to be used in the given context.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose translation will be returned.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The translated entity.
   */
  protected function getTranslationFromContext(EntityInterface $entity): EntityInterface {
    $this->entityRepository ??= \Drupal::service('entity.repository');

    return $this->entityRepository->getTranslationFromContext($entity);
  }

  /**
   * Returns the state of whether content translation needed.
   *
   * @return bool
   *   The state.
   */
  protected function needsTranslatedContent(): bool {
    if (NULL === $this->needsTranslatedContent) {
      /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
      $language_manager = \Drupal::service('language_manager');
      $default_language = $language_manager->getDefaultLanguage();
      $content_language = $language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
      $this->needsTranslatedContent = $default_language->getId() !== $content_language->getId();
    }

    return $this->needsTranslatedContent;
  }

  /**
   * Returns the entity storage.
   *
   * @param string $entity_type
   *   The entity type ID.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   */
  private function getStorage(string $entity_type): EntityStorageInterface {
    $this->entityTypeManager ??= \Drupal::service('entity_type.manager');

    return $this->entityTypeManager->getStorage($entity_type);
  }

}
