<?php

namespace Drupal\yukon_w3_migrate\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for landing-page routing.
 */
class ImportDataController extends ControllerBase {
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

    // For landing page.
    $db = Database::getConnection();
    $query = $db->select("node_field_data", "n");
    $query->fields("n", ["nid", "title"]);
    $query->condition("n.type", "landing_page");
    $query->condition("n.langcode", "en");
    $results = $query->execute()->fetchAll();

    foreach ($results as $result) {
      $query = $db->select("node__field_primary_content", "p");
      $query->fields("p", []);
      $query->condition("p.bundle", "landing_page");
      $query->condition("p.entity_id", $result->nid);
      $query->condition("p.langcode", "en");
      $primary_cont = $query->execute()->fetchAll();

      $query = $db->select("paragraphs_item_field_data", "i");
      $query->fields("i", []);
      $query->condition("i.type", "primary_content");
      $query->condition(
            "i.id",
            $primary_cont[0]->field_primary_content_target_id
        );
      $query->condition("i.langcode", "fr");
      $primary_fr = $query->execute()->fetchAll();

      if (empty($primary_fr)) {
        $query = $db->select("paragraphs_item_field_data", "i");
        $query->fields("i", []);
        $query->condition("i.type", "primary_content");
        $query->condition(
          "i.id",
          $primary_cont[0]->field_primary_content_target_id
          );
        $query->condition("i.langcode", "en");
        $primary_en = $query->execute()->fetchAll();  
        if (!empty($primary_en[0]->id)) {     
          $db->update("paragraphs_item_field_data")
            ->fields([
              "revision_translation_affected" => NULL,
            ])
            ->condition("id", $primary_en[0]->id, "=")
            ->execute();

          $result = $db
            ->insert("paragraphs_item_field_data")
            ->fields([
              "id" => $primary_en[0]->id,
              "revision_id" => $primary_en[0]->revision_id,
              "type" => $primary_en[0]->type,
              "langcode" => "fr",
              "status" => $primary_en[0]->status,
              "created" => $primary_en[0]->created,
              "parent_id" => $primary_en[0]->parent_id,
              "parent_type" => $primary_en[0]->parent_type,
              "parent_field_name" =>
              $primary_en[0]->parent_field_name,
              "behavior_settings" =>
              $primary_en[0]->behavior_settings,
              "default_langcode" => 0,
              "revision_translation_affected" => 1,
              "content_translation_source" => "en",
              "content_translation_outdated" =>
              $primary_en[0]->content_translation_outdated,
              "content_translation_changed" =>
              $primary_en[0]->content_translation_changed,
            ])
            ->execute();

          $result = $db
            ->insert("paragraphs_item_revision_field_data")
            ->fields([
              "id" => $primary_en[0]->id,
              "revision_id" => $primary_en[0]->revision_id,
              // 'type' => $primary_en[0]->type,
              "langcode" => "fr",
              "status" => $primary_en[0]->status,
              "created" => $primary_en[0]->created,
              "parent_id" => $primary_en[0]->parent_id,
              "parent_type" => $primary_en[0]->parent_type,
              "parent_field_name" =>
              $primary_en[0]->parent_field_name,
              "behavior_settings" =>
              $primary_en[0]->behavior_settings,
              "default_langcode" => 0,
              "revision_translation_affected" => 1,
              "content_translation_source" => "en",
              "content_translation_outdated" =>
              $primary_en[0]->content_translation_outdated,
              "content_translation_changed" =>
              $primary_en[0]->content_translation_changed,
            ])
            ->execute();

          $query = $db->select("paragraph__field_popular_links", "i");
          $query->fields("i", []);
          $query->condition("i.bundle", "primary_content");
          $query->condition("i.entity_id", $primary_en[0]->id);
          $primary_popular = $query->execute()->fetchAll();

          foreach ($primary_popular as $populer) {
            $result = $db
              ->insert("paragraph__field_popular_links")
              ->fields([
                "bundle" => $populer->bundle,
                "deleted" => $populer->deleted,
                "entity_id" => $populer->entity_id,
                "revision_id" => $populer->revision_id,
                "langcode" => "fr",
                "delta" => $populer->delta,
                "field_popular_links_target_id" =>
                $populer->field_popular_links_target_id,
              ])
              ->execute();

            $result = $db
              ->insert("paragraph_revision__field_popular_links")
              ->fields([
                "bundle" => $populer->bundle,
                "deleted" => $populer->deleted,
                "entity_id" => $populer->entity_id,
                "revision_id" => $populer->revision_id,
                "langcode" => "fr",
                "delta" => $populer->delta,
                "field_popular_links_target_id" =>
                $populer->field_popular_links_target_id,
              ])
              ->execute();
          }
        }
      }
    } 
    // For landing page level 2.
    $db = Database::getConnection();
    $query = $db->select("node_field_data", "n");
    $query->fields("n", ["nid", "title"]);
    $query->condition("n.type", "landing_page_level_2");
    $query->condition("n.langcode", "en");
    $results = $query->execute()->fetchAll();

    foreach ($results as $result) {
      $query = $db->select("node__field_primary_content", "p");
      $query->fields("p", []);
      $query->condition("p.bundle", "landing_page_level_2");
      $query->condition("p.entity_id", $result->nid);
      $query->condition("p.langcode", "en");
      $primary_cont = $query->execute()->fetchAll();

      $query = $db->select("paragraphs_item_field_data", "i");
      $query->fields("i", []);
      $query->condition("i.type", "primary_content");
      $query->condition(
            "i.id",
            $primary_cont[0]->field_primary_content_target_id
        );
      $query->condition("i.langcode", "fr");
      $primary_fr = $query->execute()->fetchAll();

      if (empty($primary_fr)) {
        $query = $db->select("paragraphs_item_field_data", "i");
        $query->fields("i", []);
        $query->condition("i.type", "primary_content");
        $query->condition(
          "i.id",
          $primary_cont[0]->field_primary_content_target_id
          );
        $query->condition("i.langcode", "en");
        $primary_en = $query->execute()->fetchAll();

        if (!empty($primary_en[0]->id)) {
          $db->update("paragraphs_item_field_data")
            ->fields([
              "revision_translation_affected" => NULL,
            ])
            ->condition("id", $primary_en[0]->id, "=")
            ->execute();

          $result = $db
            ->insert("paragraphs_item_field_data")
            ->fields([
              "id" => $primary_en[0]->id,
              "revision_id" => $primary_en[0]->revision_id,
              "type" => $primary_en[0]->type,
              "langcode" => "fr",
              "status" => $primary_en[0]->status,
              "created" => $primary_en[0]->created,
              "parent_id" => $primary_en[0]->parent_id,
              "parent_type" => $primary_en[0]->parent_type,
              "parent_field_name" =>
              $primary_en[0]->parent_field_name,
              "behavior_settings" =>
              $primary_en[0]->behavior_settings,
              "default_langcode" => 0,
              "revision_translation_affected" => 1,
              "content_translation_source" => "en",
              "content_translation_outdated" =>
              $primary_en[0]->content_translation_outdated,
              "content_translation_changed" =>
              $primary_en[0]->content_translation_changed,
            ])
            ->execute();

          $result = $db
            ->insert("paragraphs_item_revision_field_data")
            ->fields([
              "id" => $primary_en[0]->id,
              "revision_id" => $primary_en[0]->revision_id,
                  // 'type' => $primary_en[0]->type,
              "langcode" => "fr",
              "status" => $primary_en[0]->status,
              "created" => $primary_en[0]->created,
              "parent_id" => $primary_en[0]->parent_id,
              "parent_type" => $primary_en[0]->parent_type,
              "parent_field_name" =>
              $primary_en[0]->parent_field_name,
              "behavior_settings" =>
              $primary_en[0]->behavior_settings,
              "default_langcode" => 0,
              "revision_translation_affected" => 1,
              "content_translation_source" => "en",
              "content_translation_outdated" =>
              $primary_en[0]->content_translation_outdated,
              "content_translation_changed" =>
              $primary_en[0]->content_translation_changed,
            ])
            ->execute();

          $query = $db->select("paragraph__field_popular_links", "i");
          $query->fields("i", []);
          $query->condition("i.bundle", "primary_content");
          $query->condition("i.entity_id", $primary_en[0]->id);
          $primary_popular = $query->execute()->fetchAll();

          foreach ($primary_popular as $populer) {
            $result = $db
              ->insert("paragraph__field_popular_links")
              ->fields([
                "bundle" => $populer->bundle,
                "deleted" => $populer->deleted,
                "entity_id" => $populer->entity_id,
                "revision_id" => $populer->revision_id,
                "langcode" => "fr",
                "delta" => $populer->delta,
                "field_popular_links_target_id" =>
                $populer->field_popular_links_target_id,
              ])
              ->execute();

            $result = $db
              ->insert("paragraph_revision__field_popular_links")
              ->fields([
                "bundle" => $populer->bundle,
                "deleted" => $populer->deleted,
                "entity_id" => $populer->entity_id,
                "revision_id" => $populer->revision_id,
                "langcode" => "fr",
                "delta" => $populer->delta,
                "field_popular_links_target_id" =>
                $populer->field_popular_links_target_id,
              ])
              ->execute();
          }
        }
      }
    }
    $this->messenger()->addMessage("Updates are successfully done");
    return [
      '#markup' => '<p></p>',
    ];
  }

}
