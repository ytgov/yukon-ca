#!/bin/bash

./vendor/bin/drush migrate:reset-status yukon_migrate_transactional_facilities
./vendor/bin/drush migrate:import --update --continue-on-failure yukon_migrate_transactional_facilities