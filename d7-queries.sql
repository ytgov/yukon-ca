-- Main - Update Revision IDs to fix Paragraph unprocessed count
--
-- This should be run against (a clone of) the source database before the migration.
--
-- Can be run from Drush:
--     drush sql:query --database=migrate --file=d7-queries.sql

UPDATE field_data_field_opening_time_paragraph a
INNER JOIN field_revision_field_opening_time b
    ON a.field_opening_time_paragraph_value = b.entity_id
SET a.field_opening_time_paragraph_revision_id = b.revision_id
WHERE b.revision_id = (
    SELECT MAX(revision_id)
    FROM field_revision_field_opening_time
    WHERE entity_id = b.entity_id
);

UPDATE paragraphs_item a INNER JOIN field_data_field_secondary_content b on b.field_secondary_content_value = a.item_id SET a.revision_id = b.field_secondary_content_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_image_gallery b on b.field_image_gallery_value = a.item_id SET a.revision_id = b.field_image_gallery_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_primary_content b on b.field_primary_content_value = a.item_id SET a.revision_id = b.field_primary_content_revision_id;

UPDATE paragraphs_item a INNER JOIN field_data_field_sections b on b.field_sections_value = a.item_id SET a.revision_id = b.field_sections_revision_id;

UPDATE paragraphs_item a INNER JOIN field_data_field_record_type b on b.field_record_type_value = a.item_id SET a.revision_id = b.field_record_type_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_news_quote b on b.field_news_quote_value = a.item_id SET a.revision_id = b.field_news_quote_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_quick_facts b on b.field_quick_facts_value = a.item_id SET a.revision_id = b.field_quick_facts_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_opening_time_paragraph b on b.field_opening_time_paragraph_value = a.item_id SET a.revision_id = b.field_opening_time_paragraph_revision_id;

UPDATE paragraphs_item a INNER JOIN field_data_field_opening_times b on b.field_opening_times_value = a.item_id SET a.revision_id = b.field_opening_times_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_holiday_hours b on b.field_holiday_hours_value = a.item_id SET a.revision_id = b.field_holiday_hours_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_social_network b on b.field_social_network_value = a.item_id SET a.revision_id = b.field_social_network_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_navigation_jump_point b on b.field_navigation_jump_point_value = a.item_id SET a.revision_id = b.field_navigation_jump_point_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_flexible_content b on b.field_flexible_content_value = a.item_id SET a.revision_id = b.field_flexible_content_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_add_branch b on b.field_add_branch_value = a.item_id SET a.revision_id = b.field_add_branch_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_add_primary_top_tasks b on b.field_add_primary_top_tasks_value = a.item_id SET a.revision_id = b.field_add_primary_top_tasks_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_add_secondary_top_tasks b on b.field_add_secondary_top_tasks_value = a.item_id SET a.revision_id = b.field_add_secondary_top_tasks_revision_id;

UPDATE paragraphs_item a INNER JOIN field_data_field_sub_headers b on b.field_sub_headers_value = a.item_id SET a.revision_id = b.field_sub_headers_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_contact_person b on b.field_contact_person_value = a.item_id SET a.revision_id = b.field_contact_person_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_collapsable_field b on b.field_collapsable_field_value = a.item_id SET a.revision_id = b.field_collapsable_field_revision_id;
UPDATE paragraphs_item a INNER JOIN field_data_field_charts b on b.field_charts_value = a.item_id SET a.revision_id = b.field_charts_revision_id;

UPDATE IGNORE `field_data_field_related_tasks` SET `language`='en' WHERE `language` = 'und';
UPDATE IGNORE `field_revision_field_related_tasks` SET `language`='en' WHERE `language` = 'und';