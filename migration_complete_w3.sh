#!/bin/bash

COMMAND="./vendor/bin/drush"

echo "URL_alias..."

$COMMAND migrate:import --group=legacy_url_alias  --continue-on-failure
