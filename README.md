# Yukon.ca

Drupal 10 Drupal build for Yukon.ca. This repository was prepared so it's sources can be built via CI to deploy to Pantheon.

[[_TOC_]]

## Local environment setup

For local environment setup, please check detailed instructions [here](LocalSetup.md).

There is an entry in sites/default/settings/local.settings.php that points to the same DB container for the secondary DB for migration, named migrate. You can populate it if you have the DB dump:

```
$ fin db create migrate
$ zcat drupal7.sql.gz | fin db import --db=migrate
```

## Architecture

Layout Builder: The current standard choice for components implementation is Layout Builder, so Paragraphs should normally map to Block types, exceptions apart.


## Components

`TODO`: list components from Audit

## External Integrations

`TODO`: Document SSO solution here.

## Development workflow

Every new development should be pushed to a development branch. The advice is to keep the names short: due to Pantheon's limitation, multidev cannot use longer branch names, so in case we want to use it, we should avoid the long names.

Upon pushing to a branch, you can create a Pull Request for EW to review. Branches are pushed to Pantheon so you can also spin a Multidev environment to demonstrate before merging.

## Resources

* Git Repo - `TODO` Add git repo URL here.
* Cloud subscription - `TODO` Add URL here.
* Dev site - `TODO` Add dev site URL here.
* Stage site - `TODO` Add stage site URL here.
* Prod site - `TODO` Add prod site URL here.

----------------------------------
----------------------------------

## Setup Druapl 10 website

The first step of upgrading the Druapl 7 vesrion to Drupal 10 is setting up a blank Drupal 10 website by using the following steps:

1. Setup a web server
2. Copy the file system from the master branch
3. Run composer update
4. Create database and assign user
6. Enter the database credentails in the sites/default/settings.php file
7. Hit the website URL to complete the installation process
8. Please note that while setting up the website, we need to select "minimal" profile. Selecting other profiles will generate issues.

## Migrating the data from Yukon.ca Drupal 7 version to Drupal 10

Once blank Drupal 10 website is done, data import can be initiated by adding database credentials of the Drupal 7 website in the sites/default/settings.php file. Do not remove the Drupal 10 database credentials and instead add Drupal 7 credentials in migrate database (second database credentails). 

## Migration - Overall process

There are more than 30K nodes on the D7 version and it can take anywhere between 10 to 15 hours to run the complete migration.  The speed of migration depends on the server (both D7 and D10) and on the size of data. It needs manual monitoring and validation to confirm that data migration was completed as required. To make this process feasible, the migration process has been divided into 10 batches and we need to run this migration at least two times (10 batches x 2 times). In the first round we get the migration data and in the second round, we fix the missing relationships between the nodes.    
 
### Migration - 1st Round to migrate initial data

Running the following 10 commands will import the data from Drupal 7 to Druapl 10. Please note that this is the start of migration where Drupal 10 has no data from the production website. Running these commands for the second time is recommended only if data import was not complete or got corrupted. Partial imports can be done by running individual commands where all previous node IDs will be updated (assigned new).

```
./vendor/bin/drush migrate:import --group=legacy_taxonomies --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_media --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_paragraphs --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_nodes --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_documents --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_basic_page --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_page_news --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_user_role --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_menu continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_files --continue-on-failure
```

The script `migrate_initial.sh` does the above.

### Reset migration in case of failure

Migrations can fail to complete due to multiple reasons and when it happens, it display the name of the table for which migration stopped working.  Rerunning (resume) the migration is only possible after resetting the migration using a command like below where “yukon_migrate_landing_page” is the name of the failed table. 

    ./vendor/bin/drush migrate:reset-status yukon_migrate_landing_page


### Update - 2nd Round to update relationships

```
./vendor/bin/drush migrate:import --group=legacy_taxonomies --update --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_media --update --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_paragraphs --update --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_nodes --update --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_documents --update --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_basic_page --update --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_page_news --update --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_user_role --update --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_menu --update --continue-on-failure
./vendor/bin/drush migrate:import --group=legacy_files --update --continue-on-failure
```

The script `migrate_update.sh` does the above.

Running the above commands more than once is recommended.

### Post-import cleanup

The `custom_migrate` module does some post-migration clean up work. Visit `[root-URL]/custom_migrate` to execute that code.

## Run the following commands after migrating the data:

After completing the data migration and its update, the following commands may be run to setup the theme files.

npm run build command inside [web-root]/docroot/themes/custom/yukonca_glider

Clear Drupal cache using drush cr

Drupal 10 website should be ready at this point

----------------------------------
----------------------------------

## About Issue #126

This issue is related to incomplete migration.  This will be fixed either by re-running the complete migration or by using the steps below: 

### Rollback and Import Primary Content:

Please note that roll back commands will assign new node IDs and will need migration update cammands to be re-run.

./vendor/bin/drush migrate:rollback yukon_migrate_primary_content

./vendor/bin/drush migrate:import yukon_migrate_primary_content

### Rollback and Import Landing Page:

./vendor/bin/drush migrate:rollback yukon_migrate_landing_page

./vendor/bin/drush migrate:rollback yukon_migrate_landing_page_translations

./vendor/bin/drush migrate:import yukon_migrate_landing_page

./vendor/bin/drush migrate:import yukon_migrate_landing_page_translations

### Export and Import of the custom translations:

#### Examples

##### Export
- Local
```bash
drush locale-export fr --type=customized > config/translations/fr.customized.po
```
- Pantheon
```bash
drush locale-export fr --type=customized > /code/config/translations/fr.customized.po
```

##### Import:
- Local
```bash
drush locale-import fr --type=customized /var/www/html/config/translations/fr.customized.po
```
- Pantheon
```bash
drush locale-import fr --type=customized /code/config/translations/fr.customized.po
```

##### Import with override:
- Local
```bash
drush locale-import fr --type=customized --override=customized /var/www/html/config/translations/fr.customized.po
```
- Pantheon
```bash
drush locale-import fr --type=customized --override=customized /code/config/translations/fr.customized.po
```


