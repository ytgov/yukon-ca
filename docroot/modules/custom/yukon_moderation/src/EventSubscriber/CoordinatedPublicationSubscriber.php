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
 * is automatically published at the same time.
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

    // Only react when the default (English) translation is published.
    if ($event->getNewState() !== 'published') {
      return;
    }

    $auto_published = [];
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
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

}
