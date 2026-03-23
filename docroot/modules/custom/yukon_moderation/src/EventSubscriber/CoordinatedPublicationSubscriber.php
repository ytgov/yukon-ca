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
    // Only react when transitioning to published.
    if ($event->getNewState() !== 'published') {
      return;
    }

    $entity = $event->getModeratedEntity();

    // Only apply to nodes.
    if (!$entity instanceof NodeInterface) {
      return;
    }

    // Only apply when the default translation (English) is published.
    // This also prevents recursion when we save a translated node below.
    if (!$entity->isDefaultTranslation()) {
      return;
    }

    $default_langcode = $entity->language()->getId();
    $auto_published = [];

    foreach ($entity->getTranslationLanguages(FALSE) as $langcode => $language) {
      if ($langcode === $default_langcode) {
        continue;
      }

      $translation = $entity->getTranslation($langcode);
      if ($translation->moderation_state->value === self::COORDINATED_STATE) {
        $translation->set('moderation_state', 'published');
        $translation->save();
        $auto_published[] = $language->getName();
      }
    }

    if (!empty($auto_published)) {
      \Drupal::messenger()->addStatus(t('The following translations were automatically published along with "@title": @langs.', [
        '@title' => $entity->label(),
        '@langs' => implode(', ', $auto_published),
      ]));
    }
  }

}
