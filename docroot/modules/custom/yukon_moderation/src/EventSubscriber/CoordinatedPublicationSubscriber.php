<?php

namespace Drupal\yukon_moderation\EventSubscriber;

use Drupal\node\NodeInterface;
use Drupal\workbench_email\EventSubscriber\ContentModerationStateChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to content moderation state changes.
 *
 * When the default (English) translation of a node is published, any
 * non-default translation in the "Ready for Coordinated Publication" state
 * is automatically published at the same time. When the default translation
 * is archived, all non-default translations are archived too.
 */
class CoordinatedPublicationSubscriber implements EventSubscriberInterface {

  /**
   * The moderation state that triggers coordinated publication.
   */
  const COORDINATED_STATE = 'ready_for_coordinated_publication';

  /**
   * State API key prefix for tracking pending coordinated publications.
   */
  const STATE_KEY_PREFIX = 'yukon_moderation.coordinated_pending.';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      'content_moderation.state_changed' => 'onStateChanged',
    ];
  }

  /**
   * Reacts to a content moderation state change.
   *
   * @param \Drupal\workbench_email\EventSubscriber\ContentModerationStateChangedEvent $event
   *   The state changed event.
   */
  public function onStateChanged(ContentModerationStateChangedEvent $event): void {
    $entity = $event->getModeratedEntity();

    // Only apply to nodes.
    if (!$entity instanceof NodeInterface) {
      return;
    }

    if ($event->getNewState() === 'archived') {
      $this->archiveTranslations($entity);
      return;
    }

    // Only react when the default (English) translation is published.
    if ($event->getNewState() !== 'published') {
      return;
    }

    $auto_published = [];
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    foreach ($entity->getTranslationLanguages(FALSE) as $lang_code => $language) {
      $translation = $entity->getTranslation($lang_code);

      $key = self::STATE_KEY_PREFIX . $entity->id() . '.' . $lang_code;
      $is_rfcp_in_entity = $translation->moderation_state->value === self::COORDINATED_STATE;
      $rfcp_rid = \Drupal::state()->get($key);
      $is_rfcp_pending = (bool) $rfcp_rid;

      if (!$is_rfcp_in_entity && !$is_rfcp_pending) {
        continue;
      }

      // When the rfcp state was set on an existing node, copy the translator's
      // content fields from that revision before publishing, since the English
      // revision chain may not include those changes.
      if ($is_rfcp_pending && $rfcp_rid) {
        $rfcp_revision = $node_storage->loadRevision($rfcp_rid);
        if ($rfcp_revision instanceof NodeInterface && $rfcp_revision->hasTranslation($lang_code)) {
          $rfcp_translation = $rfcp_revision->getTranslation($lang_code);
          $skip = ['moderation_state', 'revision_translation_affected', 'langcode'];
          foreach ($rfcp_translation->getTranslatableFields() as $field_name => $field) {
            if (in_array($field_name, $skip, TRUE)) {
              continue;
            }
            $translation->set($field_name, $rfcp_translation->get($field_name)->getValue());
          }
        }
      }

      $translation->set('moderation_state', 'published');
      $translation->save();
      $auto_published[] = $language->getName();
    }

    if (!empty($auto_published)) {
      \Drupal::messenger()->addStatus(t('The following translations were automatically published along with "@title": @langs.', [
        '@title' => $entity->label(),
        '@langs' => implode(', ', $auto_published),
      ]));
    }
  }

  /**
   * Archives all non-default translations when the default one is archived.
   *
   * The event fires for any translation's state change but carries the
   * default-translation entity, so check that translation's own state: this
   * keeps archiving French alone from cascading to English. See
   * docs/coordinated-translation-archiving.md.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The moderated node.
   */
  protected function archiveTranslations(NodeInterface $node): void {
    $default = $node->getUntranslated();
    if ($default->moderation_state->value !== 'archived') {
      return;
    }

    $auto_archived = [];
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    foreach ($default->getTranslationLanguages(FALSE) as $lang_code => $language) {
      // Build from the language's own latest revision so a pending edit isn't
      // bypassed by a default-revision snapshot.
      $rid = $node_storage->getLatestTranslationAffectedRevisionId($default->id(), $lang_code);
      $revision = $rid ? $node_storage->loadRevision($rid) : NULL;
      if (!$revision instanceof NodeInterface || !$revision->hasTranslation($lang_code)) {
        continue;
      }
      $translation = $revision->getTranslation($lang_code);
      if ($translation->moderation_state->value === 'archived') {
        continue;
      }

      $translation->set('moderation_state', 'archived');
      $translation->setNewRevision(TRUE);
      $translation->save();
      $auto_archived[] = $language->getName();
    }

    if (!empty($auto_archived)) {
      \Drupal::messenger()->addStatus(t('The following translations were automatically archived along with "@title": @langs.', [
        '@title' => $default->label(),
        '@langs' => implode(', ', $auto_archived),
      ]));
    }
  }

}
