<?php

namespace Drupal\yukon_w3_migrate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for landing-page routing.
 */
class TimeChangeController extends ControllerBase {
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
    $db = Database::getConnection();

    // Closing TIme.
    $query = $db->select("paragraph__field_closing_time", "n");
    $query->fields("n", ["entity_id", "field_closing_time_value"]);
    $results = $query->execute()->fetchAll();
    foreach ($results as $result) {
      if ($result->field_closing_time_value != 0 and $result->field_closing_time_value != 30) {
        $number = '';
        $hours = '';
        $mins = '';
        $final_time = '';
        $time = $result->field_closing_time_value;
        $number = strlen($time);
        $nine_hours = '';
        $fifteen_hour = '';
        if ($number == 3) {
          $hours = substr($time, 0, 1);
          $mins = substr($time, -2);
          $hours = $hours * 60 * 60;
          $minutes = $mins * 60;
          $fifteen_hour = 15 * 60 * 60;
          $final_time = $hours + $minutes + $fifteen_hour;
        }
        else {
          $hours = substr($time, 0, 2);
          $mins = substr($time, -2);
          $hours = $hours * 60 * 60;
          $minutes = $mins * 60;
          $nine_hours = 9 * 60 * 60;
          $final_time = $hours + $minutes - $nine_hours;
        }
        $db->update("paragraph__field_closing_time")
          ->fields([
            "field_closing_time_value" => $final_time,
          ])
          ->condition('entity_id', $result->entity_id)
          ->execute();
      }
      elseif ($result->field_closing_time_value == 0) {
        $closing_hour = 15 * 60 * 60;
        $db->update("paragraph__field_closing_time")
          ->fields([
            "field_closing_time_value" => $closing_hour,
          ])
          ->condition('entity_id', $result->entity_id)
          ->execute();
      }
    }

    // Opening time.
    $query = $db->select("paragraph__field_opening_time", "n");
    $query->fields("n", ["entity_id", "field_opening_time_value"]);
    $results = $query->execute()->fetchAll();
    // dump($results); die;.
    foreach ($results as $result) {
      // dump($result); die;.
      if ($result->field_opening_time_value != 0 and $result->field_opening_time_value != 30) {
        $number = '';
        $hours = '';
        $mins = '';
        $final_time = '';
        $minutes = '';
        $time = $result->field_opening_time_value;
        $number = strlen($time);
        $nine_hours = '';
        $fifteen_hour = '';
        if ($number == 3) {
          $hours = substr($time, 0, 1);
          $mins = substr($time, -2);
          $hours = $hours * 60 * 60;
          $minutes = $mins * 60;
          $final_time = $hours + $minutes;
          $fifteen_hour = 15 * 60 * 60;
          $final_time = $hours + $minutes + $fifteen_hour;
        }
        else {
          $hours = substr($time, 0, 2);
          $mins = substr($time, -2);
          $hours = $hours * 60 * 60;
          $minutes = $mins * 60;
          $nine_hours = 9 * 60 * 60;
          $final_time = $hours + $minutes - $nine_hours;
        }
        $db->update("paragraph__field_opening_time")
          ->fields([
            "field_opening_time_value" => $final_time,
          ])
          ->condition('entity_id', $result->entity_id)
          ->execute();
      }
      elseif ($result->field_opening_time_value == 0) {
        $new_hour = 15 * 60 * 60;
        $db->update("paragraph__field_opening_time")
          ->fields([
            "field_opening_time_value" => $new_hour,
          ])
          ->condition('entity_id', $result->entity_id)
          ->execute();
      }
    }

    $this->messenger()->addMessage("Updates are successfully done");
    return [
      '#markup' => '<p></p>',
    ];
  }

}
