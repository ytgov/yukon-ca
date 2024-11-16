UPDATE `taxonomy_term_field_data` SET `description__format`='full_html' WHERE vid = 'blog_type' and langcode = 'fr'
UPDATE `taxonomy_term_field_revision` SET `description__format`='full_html' WHERE description__value != "" and langcode = 'fr';
UPDATE `node__field_site_description` SET `field_site_description_format`='full_html';
UPDATE `paragraph__field_facts` SET `field_facts_format`='full_html';
UPDATE `paragraph__field_subcategory_links` SET `field_subcategory_links_uri`=REPLACE(field_subcategory_links_uri, '/en', 'internal:') WHERE `field_subcategory_links_uri` LIKE '/en/%';
UPDATE `paragraph__field_subcategory_links` SET `field_subcategory_links_uri`=REPLACE(field_subcategory_links_uri, '/fr', 'internal:') WHERE `field_subcategory_links_uri` LIKE '/fr/%';
UPDATE `node__field_related_link` SET `field_related_link_title`= REPLACE(field_related_link_title, '&amp;', '&') WHERE `field_related_link_title` LIKE '%&amp;%';
UPDATE `paragraph__field_link` SET `field_link_uri`= 'https://[value-7]' WHERE field_link_uri LIKE 'twitter%';
DELETE FROM path_alias WHERE path LIKE '/node%'; 
TRUNCATE TABLE migrate_map_yukon_migrate_url_alias_node;