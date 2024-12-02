<?php

namespace Drupal\yukon_w3_migrate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The user section storage service.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * Constructs InviteByEmail .
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\workbench_access\UserSectionStorageInterface $user_section_storage
   *   The user section storage service.
   */
  public function __construct(MessengerInterface $messenger, EntityTypeManagerInterface $entity_type_manager, UserSectionStorageInterface $user_section_storage) {
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
    $this->userSectionStorage = $user_section_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('messenger'),
      $container->get('entity_type.manager'),
      $container->get('workbench_access.user_section_storage')
    );
  }

  /**
   * Update the translation for primary links.
   */
  public function content() {
    $entity_type = $this->entityTypeManager()->getDefinition('linkit_profile');
    \Drupal::entityDefinitionUpdateManager()->installEntityType($entity_type);
    // To get another database (here : 'second')
    $con = Database::getConnection('default', 'migrate');

    // To set the active connection.
    Database::setActiveConnection('migrate');

    // Basic page editorial team.
    $query = $con->select("field_data_field_yukon_editorial_team", "n");
    $query->fields("n", ['entity_id', 'field_yukon_editorial_team_tid']);
    $query->condition("n.bundle", "wetkit_page");
    $result_dep = $query->execute()->fetchAll();

    foreach ($result_dep as $dep) {
      $node_storage = $this->entityTypeManager->getStorage('node');
      $entity = $node_storage->load($dep->entity_id);
      $entity->set("field_yukon_editorial_team", $dep->field_yukon_editorial_team_tid);
      $entity->save();
    }

    $query = $con->select("field_data_field_node_department", "n");
    $query->fields("n", ['entity_id', 'field_node_department_tid']);
    $query->condition("n.bundle", "event");
    $result_dep = $query->execute()->fetchAll();

    foreach ($result_dep as $dep) {
      $node_storage = $this->entityTypeManager->getStorage('node');
      $entity = $node_storage->load($dep->entity_id);
      $entity->set("field_department_term", $dep->field_node_department_tid);
      $entity->save();
    }

    $query = $con->select("field_data_field_department_term", "n");
    $query->fields("n", ['entity_id', 'field_department_term_tid']);
    $query->condition("n.bundle", "landing_page_level_2");
    $result_dep = $query->execute()->fetchAll();

    foreach ($result_dep as $dep) {
      $node_storage = $this->entityTypeManager->getStorage('node');
      $entity = $node_storage->load($dep->entity_id);
      if (!empty($entity)) {
        $entity->set("field_department_term", $dep->field_department_term_tid);
        $entity->save();
      }
    }

    $query = $con->select("workbench_access_user", "n");
    $query->fields("n");
    $results = $query->execute()->fetchAll();

    foreach ($results as $result) {
      $account = $this->entityTypeManager->getStorage('user')->load($result->uid);
      $scheme_storage = $this->entityTypeManager->getStorage('access_scheme');
      $scheme = $scheme_storage->load('team');
      if (!empty($account)) {
        $this->userSectionStorage->addUser($scheme, $account, [$result->access_id]);
      }
    }

    // To set the active connection.
    Database::setActiveConnection('default');

    // Update the unpublish.
    $db = Database::getConnection();
    $query = $db->select("node_field_data", "n");
    $query->fields("n", ["nid", "status", "langcode"]);
    $query->condition("n.status", "0");
    $query->condition("n.langcode", "en");
    $results_nid = $query->execute()->fetchAll();

    foreach ($results_nid as $res) {
      $db->update("node_field_data")
        ->fields([
          "status" => "0",
        ])
        ->condition("nid", $res->nid)
        ->condition("langcode", 'fr')
        ->execute();
    }

    $db = Database::getConnection();
    $query = $db->select("node_field_revision", "n");
    $query->fields("n", ["nid", "status", "langcode"]);
    $query->condition("n.status", "0");
    $query->condition("n.langcode", "en");
    $results_nid = $query->execute()->fetchAll();

    foreach ($results_nid as $res) {
      $db->update("node_field_revision")
        ->fields([
          "status" => "0",
        ])
        ->condition("nid", $res->nid)
        ->condition("langcode", 'fr')
        ->execute();
    }

    $node_storage = $this->entityTypeManager->getStorage('node');
    $entity = $node_storage->load('7534');
    $entity->addTranslation('fr', ['title' => "Activités", 'body' => 'Activités du gouvernement du Yukon.'])->save();
    $path_alias = $this->entityTypeManager()->getStorage('path_alias')->create([
      'path' => "/node/7534",
      'alias' => "/activites",
      'langcode' => "fr",
    ]);
    $path_alias->save();

    // Update format of the quick facts.
    $db->update("paragraph__field_facts")
      ->fields([
        "field_facts_format" => "full_html",
      ])
      ->execute();

    // For landing page.
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
    // For secondary content.
    foreach ($results as $result) {
      $query = $db->select("node__field_secondary_content", "p");
      $query->fields("p", []);
      $query->condition("p.bundle", "landing_page");
      $query->condition("p.entity_id", $result->nid);
      $query->condition("p.langcode", "fr");
      $primary_cont = $query->execute()->fetchAll();

      foreach ($primary_cont as $secondary_cont) {
        $query = $db->select("paragraphs_item_field_data", "i");
        $query->fields("i", []);
        $query->condition("i.type", "secondary_content");
        $query->condition(
            "i.id",
            $secondary_cont->field_secondary_content_target_id
            );
        $query->condition("i.langcode", "fr");
        $primary_fr = $query->execute()->fetchAll();
        if (empty($primary_fr)) {
          $query = $db->select("paragraphs_item_field_data", "i");
          $query->fields("i", []);
          $query->condition("i.type", "secondary_content");
          $query->condition(
            "i.id",
            $secondary_cont->field_secondary_content_target_id
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
            $query = $db->select("paragraph__field_landing_page_level_2", "i");
            $query->fields("i", []);
            $query->condition("i.bundle", "secondary_content");
            $query->condition("i.entity_id", $primary_en[0]->id);
            $primary_popular = $query->execute()->fetchAll();

            foreach ($primary_popular as $populer) {
              $result = $db
                ->insert("paragraph__field_landing_page_level_2")
                ->fields([
                  "bundle" => $populer->bundle,
                  "deleted" => $populer->deleted,
                  "entity_id" => $populer->entity_id,
                  "revision_id" => $populer->revision_id,
                  "langcode" => "fr",
                  "delta" => $populer->delta,
                  "field_landing_page_level_2_target_id" =>
                  $populer->field_landing_page_level_2_target_id,
                ])
                ->execute();
              $result = $db
                ->insert("paragraph_revision__field_landing_page_level_2")
                ->fields([
                  "bundle" => $populer->bundle,
                  "deleted" => $populer->deleted,
                  "entity_id" => $populer->entity_id,
                  "revision_id" => $populer->revision_id,
                  "langcode" => "fr",
                  "delta" => $populer->delta,
                  "field_landing_page_level_2_target_id" =>
                  $populer->field_landing_page_level_2_target_id,
                ])
                ->execute();
            }
            $query = $db->select("paragraph__field_subcategory_links", "i");
            $query->fields("i", []);
            $query->condition("i.bundle", "secondary_content");
            $query->condition("i.entity_id", $primary_en[0]->id);
            $primary_popular = $query->execute()->fetchAll();

            foreach ($primary_popular as $populer) {
              $result = $db
                ->insert("paragraph__field_subcategory_links")
                ->fields([
                  "bundle" => $populer->bundle,
                  "deleted" => $populer->deleted,
                  "entity_id" => $populer->entity_id,
                  "revision_id" => $populer->revision_id,
                  "langcode" => "fr",
                  "delta" => $populer->delta,
                  "field_subcategory_links_uri" =>
                  $populer->field_subcategory_links_uri,
                  "field_subcategory_links_title" => $populer->field_subcategory_links_title,
                  "field_subcategory_links_options" => $populer->field_subcategory_links_options,
                ])
                ->execute();
              $result = $db
                ->insert("paragraph_revision__field_subcategory_links")
                ->fields([
                  "bundle" => $populer->bundle,
                  "deleted" => $populer->deleted,
                  "entity_id" => $populer->entity_id,
                  "revision_id" => $populer->revision_id,
                  "langcode" => "fr",
                  "delta" => $populer->delta,
                  "field_subcategory_links_uri" =>
                  $populer->field_subcategory_links_uri,
                  "field_subcategory_links_title" => $populer->field_subcategory_links_title,
                  "field_subcategory_links_options" => $populer->field_subcategory_links_options,
                ])
                ->execute();
            }
            $query = $db->select("paragraph__field_category_title", "i");
            $query->fields("i", []);
            $query->condition("i.bundle", "secondary_content");
            $query->condition("i.entity_id", $primary_en[0]->id);
            $primary_popular = $query->execute()->fetchAll();

            foreach ($primary_popular as $populer) {
              $result = $db
                ->insert("paragraph__field_category_title")
                ->fields([
                  "bundle" => $populer->bundle,
                  "deleted" => $populer->deleted,
                  "entity_id" => $populer->entity_id,
                  "revision_id" => $populer->revision_id,
                  "langcode" => "fr",
                  "delta" => $populer->delta,
                  "field_category_title_value" =>
                  $populer->field_category_title_value,
                ])
                ->execute();
              $result = $db
                ->insert("paragraph_revision__field_category_title")
                ->fields([
                  "bundle" => $populer->bundle,
                  "deleted" => $populer->deleted,
                  "entity_id" => $populer->entity_id,
                  "revision_id" => $populer->revision_id,
                  "langcode" => "fr",
                  "delta" => $populer->delta,
                  "field_category_title_value" =>
                  $populer->field_category_title_value,
                ])
                ->execute();
            }
            $query = $db->select("paragraph__field_use_landing_page_level_2_a", "i");
            $query->fields("i", []);
            $query->condition("i.bundle", "secondary_content");
            $query->condition("i.entity_id", $primary_en[0]->id);
            $primary_popular = $query->execute()->fetchAll();

            foreach ($primary_popular as $populer) {
              $result = $db
                ->insert("paragraph__field_use_landing_page_level_2_a")
                ->fields([
                  "bundle" => $populer->bundle,
                  "deleted" => $populer->deleted,
                  "entity_id" => $populer->entity_id,
                  "revision_id" => $populer->revision_id,
                  "langcode" => "fr",
                  "delta" => $populer->delta,
                  "field_use_landing_page_level_2_a_value" =>
                  $populer->field_use_landing_page_level_2_a_value,
                ])
                ->execute();
            }
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
