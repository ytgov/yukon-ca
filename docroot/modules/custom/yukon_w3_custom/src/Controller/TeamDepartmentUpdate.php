<?php

namespace Drupal\yukon_w3_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;

/**
 * Provides route responses for landing-page routing.
 */
class TeamDepartmentUpdate extends ControllerBase {
  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs messenger.
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
    // Update the unpublish.
    $db = Database::getConnection();

    // Place Yukon Editorial Teams.
    $query = $db->select("node__field_yukon_editorial_team", "n");
    $query->fields("n");
    $query->condition("n.bundle", "directory_records_places");
    $results_nid = $query->execute()->fetchAll();

    foreach ($results_nid as $res) {
      $entity_id = $res->entity_id;
      $query = $db->select("node__field_yukon_editorial_team", "n");
      $query->fields("n");
      $query->condition("n.bundle", "directory_records_places");
      $query->condition("n.langcode", "fr");
      $query->condition("n.entity_id", $entity_id);
      $results = $query->execute()->fetchAll();
      
      if (empty($results)) {
        $result = $db
          ->insert("node__field_yukon_editorial_team")
          ->fields([
            "bundle" => $res->bundle,
            "deleted" => $res->deleted,
            "entity_id" => $res->entity_id,
            "revision_id" => $res->revision_id,
            "langcode" => "fr",
            "delta" => $res->delta,
            "field_yukon_editorial_team_target_id" => $res->field_yukon_editorial_team_target_id,
          ])
          ->execute();
      }
    }
    
    // Place Yukon Editorial Teams Revision.
    $query = $db->select("node_revision__field_yukon_editorial_team", "n");
    $query->fields("n");
    $query->condition("n.bundle", "directory_records_places");
    $results_nid = $query->execute()->fetchAll();

    foreach ($results_nid as $res) {
      $entity_id = $res->entity_id;
      $query = $db->select("node_revision__field_yukon_editorial_team", "n");
      $query->fields("n");
      $query->condition("n.bundle", "directory_records_places");
      $query->condition("n.langcode", "fr");
      $query->condition("n.entity_id", $entity_id);
      $results = $query->execute()->fetchAll();
      
      if (empty($results)) {
        $result = $db
          ->insert("node_revision__field_yukon_editorial_team")
          ->fields([
            "bundle" => $res->bundle,
            "deleted" => $res->deleted,
            "entity_id" => $res->entity_id,
            "revision_id" => $res->revision_id,
            "langcode" => "fr",
            "delta" => $res->delta,
            "field_yukon_editorial_team_target_id" => $res->field_yukon_editorial_team_target_id,
          ])
          ->execute();
      }
    }

    // Place Department.
    $query = $db->select("node__field_department_term", "n");
    $query->fields("n");
    $query->condition("n.bundle", "directory_records_places");
    $results_nid = $query->execute()->fetchAll();

    foreach ($results_nid as $res) {
      $entity_id = $res->entity_id;
      $query = $db->select("node__field_department_term", "n");
      $query->fields("n");
      $query->condition("n.bundle", "directory_records_places");
      $query->condition("n.langcode", "fr");
      $query->condition("n.entity_id", $entity_id);
      $results = $query->execute()->fetchAll();
      
      if (empty($results)) {
        $result = $db
          ->insert("node__field_department_term")
          ->fields([
            "bundle" => $res->bundle,
            "deleted" => $res->deleted,
            "entity_id" => $res->entity_id,
            "revision_id" => $res->revision_id,
            "langcode" => "fr",
            "delta" => $res->delta,
            "field_department_term_target_id" => $res->field_department_term_target_id,
          ])
          ->execute();
      }
    }

    // Place Department Revision.
    $query = $db->select("node_revision__field_department_term", "n");
    $query->fields("n");
    $query->condition("n.bundle", "directory_records_places");
    $results_nid = $query->execute()->fetchAll();

    foreach ($results_nid as $res) {
      $entity_id = $res->entity_id;
      $query = $db->select("node_revision__field_department_term", "n");
      $query->fields("n");
      $query->condition("n.bundle", "directory_records_places");
      $query->condition("n.langcode", "fr");
      $query->condition("n.entity_id", $entity_id);
      $results = $query->execute()->fetchAll();
      
      if (empty($results)) {
        $result = $db
          ->insert("node_revision__field_department_term")
          ->fields([
            "bundle" => $res->bundle,
            "deleted" => $res->deleted,
            "entity_id" => $res->entity_id,
            "revision_id" => $res->revision_id,
            "langcode" => "fr",
            "delta" => $res->delta,
            "field_department_term_target_id" => $res->field_department_term_target_id,
          ])
          ->execute();
      }
    }
      
    return [
      '#markup' => '<p> ' . $this->t('Updates are successfully done') . ' </h2>',
    ];
  }

}
