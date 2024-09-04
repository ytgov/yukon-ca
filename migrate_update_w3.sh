#!/bin/bash

COMMAND="./vendor/bin/drush"

echo "legacy_taxonomies..."
$COMMAND migrate:import --update --continue-on-failure --group=legacy_taxonomies 
echo "legacy_media..."
$COMMAND migrate:import --update --continue-on-failure --group=legacy_media 
echo "legacy_paragraphs..."
$COMMAND migrate:import --update --continue-on-failure --group=legacy_paragraphs 
echo "legacy_nodes..."
$COMMAND migrate:import --update --continue-on-failure --group=legacy_nodes 
echo "legacy_documents..."
$COMMAND migrate:import --update --continue-on-failure --group=legacy_documents
echo "legacy_basic_page..."
$COMMAND migrate:import --update --continue-on-failure --group=legacy_basic_page
echo "legacy_page_news..."
$COMMAND migrate:import --update --continue-on-failure --group=legacy_page_news
echo "legacy_user_role..."
$COMMAND migrate:import --update --continue-on-failure --group=legacy_user_role
echo "legacy_menu..."
$COMMAND migrate:import --update --continue-on-failure --group=legacy_menu
echo "legacy_files..."
$COMMAND migrate:import --update --continue-on-failure --group=legacy_files
echo "URL_alias..."
$COMMAND migrate:import --update --continue-on-failure --group=legacy_url_alias

