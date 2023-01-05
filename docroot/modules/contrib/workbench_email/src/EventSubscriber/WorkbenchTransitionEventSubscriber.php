<?php

namespace Drupal\workbench_email\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_email\QueuedEmail;
use Drupal\workbench_email\TemplateInterface;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to transition changes to send notification emails.
 */
class WorkbenchTransitionEventSubscriber implements EventSubscriberInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, QueueFactory $queue_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->queueFactory = $queue_factory;
  }

  /**
   * Event handler for Content Moderation.
   *
   * @param \Drupal\content_moderation\Event\ContentModerationStateChangedEvent $event
   *   The event listened to.
   */
  public function onContentModerationTransition($event) {
    $entity = $event->getModeratedEntity();
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entityTypeManager->getStorage('workflow')->load($event->getWorkflow());
    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $type_plugin */
    $type_plugin = $workflow->getTypePlugin();
    if (!$event->getOriginalState()) {
      $from = $type_plugin->getInitialState($entity)->id();
    }
    else {
      $from = $event->getOriginalState();
    }
    $to = $event->getNewState();

    try {
      $transition = $type_plugin->getTransitionFromStateToState($from, $to);
    }
    catch (\InvalidArgumentException $e) {
      // Do nothing in case of invalid transition.
      return;
    }

    $queue = $this->queueFactory->get('workbench_email_send' . PluginBase::DERIVATIVE_SEPARATOR . $entity->getEntityTypeId());
    foreach ($this->getTemplatesForTransition($workflow, $transition) as $template) {
      if ($template->getBundles() && !in_array($entity->getEntityTypeId() . ':' . $entity->bundle(), $template->getBundles(), TRUE)) {
        // Continue, invalid bundle.
        continue;
      }
      foreach ($this->prepareRecipients($entity, $template) as $to) {
        $queue->createItem(new QueuedEmail($template, $entity->uuid(), $to));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'content_moderation.state_changed' => 'onContentModerationTransition',
    ];
  }

  /**
   * Prepares the recipient list given the entity and template combination.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity being transitioned.
   * @param \Drupal\workbench_email\TemplateInterface $template
   *   Template being used.
   *
   * @return array
   *   Array of email addresses to send to.
   */
  protected function prepareRecipients(ContentEntityInterface $entity, TemplateInterface $template) {
    return $template->getRecipients($entity);
  }

  /**
   * Gets all templates for a transition.
   *
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow.
   * @param \Drupal\workflows\TransitionInterface $transition
   *   The transition.
   *
   * @return \Drupal\workbench_email\TemplateInterface[]
   *   Templates for the transition.
   */
  protected function getTemplatesForTransition(WorkflowInterface $workflow, TransitionInterface $transition): array {
    $storage = $this->entityTypeManager->getStorage('workbench_email_template');
    return array_reduce($storage->loadMultiple(), function (array $carry, TemplateInterface $template) use ($workflow, $transition): array {
      if (array_key_exists($transition->id(), $template->getTransitions()[$workflow->id()] ?? [])) {
        $carry[] = $template;
      }
      return $carry;
    }, []);
  }

}
