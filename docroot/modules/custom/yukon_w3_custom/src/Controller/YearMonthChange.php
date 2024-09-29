<?php

namespace Drupal\yukon_w3_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides route responses for landing-page routing.
 */
class YearMonthChange extends ControllerBase {
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
  public function content($year) {
    $current_year = date('Y');
    $month = [];
    if ($current_year == $year) {
      $current_month = date('m');
      for ($m = $current_month; $m <= 12; ++$m) {
        $month[strtolower(date('F', mktime(0, 0, 0, $m, 1)))] = date('M', mktime(0, 0, 0, $m, 1));
      }
    }
    else {
      for ($m = 1; $m <= 12; ++$m) {
        $month[strtolower(date('F', mktime(0, 0, 0, $m, 1)))] = date('M', mktime(0, 0, 0, $m, 1));
      }
    }
    return new JsonResponse([
      $month,
    ]);
  }

}
