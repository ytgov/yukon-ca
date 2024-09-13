#!/bin/bash

COMMAND="./vendor/bin/drush"

echo "legacy_user_role..."
$COMMAND migrate:import --group=legacy_user_role --update  --continue-on-failure
echo "legacy_taxonomies..."
$COMMAND migrate:import --group=legacy_taxonomies --update  --continue-on-failure
echo "legacy_media..."
$COMMAND migrate:import --group=legacy_media --update  --continue-on-failure
echo "legacy_paragraphs..."
$COMMAND migrate:import --group=legacy_paragraphs --update  --continue-on-failure
echo "legacy_documents..."
$COMMAND migrate:import --group=legacy_documents --update  --continue-on-failure
echo "legacy_menu..."
$COMMAND migrate:import --group=legacy_menu --update  --continue-on-failure
echo "legacy_files..."
$COMMAND migrate:import --group=legacy_files --update  --continue-on-failure
echo "legacy_nodes..."
$COMMAND migrate:import --group=legacy_nodes --update  --continue-on-failure
echo "legacy_basic_page..."
$COMMAND migrate:import --group=legacy_basic_page --update  --continue-on-failure
echo "legacy_page_news..."
$COMMAND migrate:import --group=legacy_page_news --update  --continue-on-failure
echo "URL_alias..."
$COMMAND migrate:import --group=legacy_url_alias --update  --continue-on-failure
