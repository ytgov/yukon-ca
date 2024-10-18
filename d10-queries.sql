// Update format for blog type description french translation: 

UPDATE `taxonomy_term_field_data` SET `description__format`='full_html' WHERE vid = 'blog_type' and langcode = 'fr'
UPDATE `taxonomy_term_field_revision` SET `description__format`='full_html' WHERE description__value != "" and langcode = 'fr';

UPDATE `node__field_site_description` SET `field_site_description_format`='full_html' WHERE 1
UPDATE `paragraph__field_facts` SET `field_facts_format`='full_html' WHERE 1

UPDATE `paragraph__field_subcategory_links` SET `field_subcategory_links_uri`=REPLACE(field_subcategory_links_uri, '/en', 'internal:') WHERE `field_subcategory_links_uri` LIKE '/en/%';

UPDATE `paragraph__field_subcategory_links` SET `field_subcategory_links_uri`=REPLACE(field_subcategory_links_uri, '/fr', 'internal:') WHERE `field_subcategory_links_uri` LIKE '/fr/%';

// Update path_alias table

DELETE FROM path_alias WHERE path LIKE '/node%'; 

TRUNCATE TABLE migrate_map_yukon_migrate_url_alias_node;