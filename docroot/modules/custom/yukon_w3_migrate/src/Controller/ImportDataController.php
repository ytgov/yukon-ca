    // For secondary content.
    foreach ($results as $result) {
      $query = $db->select("node__field_secondary_content", "p");
      $query->fields("p", []);
      $query->condition("p.bundle", "landing_page");
      $query->condition("p.entity_id", $result->nid);
      $query->condition("p.langcode", "fr");
      $primary_cont = $query->execute()->fetchAll();

      foreach($primary_cont as $secondary_cont) {
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
    
                // $result = $db
                //   ->insert("paragraph_revision__field_category_title")
                //   ->fields([
                //     "bundle" => $populer->bundle,
                //     "deleted" => $populer->deleted,
                //     "entity_id" => $populer->entity_id,
                //     "revision_id" => $populer->revision_id,
                //     "langcode" => "fr",
                //     "delta" => $populer->delta,
                //     "field_category_title_value" =>
                //     $populer->field_category_title_value,
                //   ])
                //   ->execute();
              }
              
              
            }
          }
      }
    }
