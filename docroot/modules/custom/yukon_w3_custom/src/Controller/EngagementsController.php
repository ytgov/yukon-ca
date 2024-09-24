<?php

namespace Drupal\yukon_w3_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for landing-page routing.
 */
class EngagementsController extends ControllerBase {
  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs InviteByEmail .
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('messenger')
    );
  }

  /**
   * Update the translation for primary links.
   */
  public function content() {
    return [
      '#markup' => '<h2 class="pane-title"> ' . $this->t('Give your input') . ' </h2>',
    ];
  }

}
