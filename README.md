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
3. Run composer install
4. Create database and assign user
6. Enter the database credentails in the sites/default/settings.php file
7. Go to sites/settings.php and comment the lines
 
 if (!isset($databases['migrate'])) {
    $databases['migrate'] = $databases['default'];
    $databases['migrate']['default']['database'] = 'migrate';
 }

  Automatically generated include for settings managed by ddev.
  $ddev_settings = dirname(__FILE__) . '/settings.ddev.php';
  if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
    require $ddev_settings;
 }

7. Hit the website URL to complete the installation process
8. Please note that while setting up the website, we need to select "minimal" profile. Selecting other profiles will generate issues.
9. Enter migrate Drupal 7 DB code and credentials in settings.php at the end. This is to connect the migration source.

   $databases['migrate']['default'] = array (
  'database' => 'D7_database_name',
  'username' => 'D7_database_user',
  'password'  => 'D7_database_password',
  'host'      => 'localhost',
  'port'      => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver'    => 'mysql',
); 

10. Go to the terminal >> public_html and run this command to get the UUID (copy the UUID)
    
    ./vendor/bin/drush cget "system.site" uuid

11. Once UUID is copied, go to config/default/system.site.yml and the replace the existing with the copied one.
12. Go to the terminal >> public_html and run this command (select yes on prompt). Ignore the first error and run it again. After it gets completed, run it for the 3rd/4th time until only two files are left for the update. 

    ./vendor/bin/drush cim

13. Clear cache >>  ./vendor/bin/drush cr
14. Run the d7-queries.php on the Drupal 7 database.
15. Run migration using migrate_initial_and_update_w3.sh and leave it running for the next few hours until complete. (approxximate time 10+ hours)
16. Run the d10-queries.php on the Drupal 10 database.
17. Run the second migration using migration_complete_w3.sh and let it complete. (approxximate time 1 hour)
18. Go to the terminal >> public_html and clear cache >>  ./vendor/bin/drush cr
19. Theme setup. Go to the terminal >> cd public_html/docroot/themes/custom/yukonca_glider/
20. Assuming that the correct node version is already installed run >> npm run build
21. Go to the terminal >> public_html and clear cache >>  ./vendor/bin/drush cr
22. Import French translations by hitting <root domain>/admin/config/regional/translate/import. On this admim interface, select the fr.po file, select "French" in the dropdown, check the three boxes and click upload.
22. Migration process is complete at this point.
23. If some files are not working, then manually replacing the files folder with the D7 version can fix those errors. 
24. We need to add "More on Yukon.ca" Hamburger menu 


## Extra Step-1: (Only required where URL carries /docroot in the URL)

/docroot/themes/custom/yukonca_glider/patterns/organisms/footer/scss/styles.scss
Line 6 >> remove /docroot

/docroot/themes/custom/yukonca_glider/src/js/custom.js
Line 18 >> remove /docroot


## Extra Step-2: (Only required node is not installed or version is incorrect)

Node Installation followed by theme setup:

curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.1/install.sh | bash
nvm --version
nvm install 16
nvm install 18

Theme setup
nvm use v16.16.0
npm install
nvm use v18
npm run build

## How to arrange the of Homepage item boxes:

Edit Homepage and drag the "Primary item blocks" and "Secondary item blocks" up and down as required. Click "Save".

## Migrating the data from Yukon.ca Drupal 7 version to Drupal 10

Once blank Drupal 10 website is done, data import can be initiated by adding database credentials of the Drupal 7 website in the sites/default/settings.php file. Do not remove the Drupal 10 database credentials and instead add Drupal 7 credentials in migrate database (second database credentails). 

## Migration - Overall process

There are more than 30K nodes on the D7 version and it can take anywhere between 10 to 15 hours to run the complete migration.  The speed of migration depends on the server (both D7 and D10) and on the size of data. It needs manual monitoring and validation to confirm that data migration was completed as required. To make this process feasible, the migration process has been divided into 11 batches and we need to run this migration at least two times (11 batches x 2 times). In the first round we get the migration data and in the second round, we fix the missing relationships between the nodes.

### Migration - Prep D7 source database

To fix an issue with Paragraph revisions, run the `d7-queries.sql` against the source DB.

  drush sql:query --database=migrate --file=d7-queries.sql

### Migration - Initial and update

The script `migrate_initial_and_update_w3.sh` does an initial import, and the two subsequent updates, along with some other minor tweaks.

### Reset migration in case of failure

Migrations can fail to complete due to multiple reasons and when it happens, it display the name of the table for which migration stopped working.  Rerunning (resume) the migration is only possible after resetting the migration using a command like below where “yukon_migrate_landing_page” is the name of the failed table.

    ./vendor/bin/drush migrate:reset-status yukon_migrate_landing_page

Running the above commands more than once is recommended.

### Post-import cleanup

The `custom_migrate` module does some post-migration clean up work. Visit `[root-URL]/custom_migrate` to execute that code.

## Run the following commands after migrating the data:

After completing the data migration and its update, the following commands may be run to setup the theme files.

npm run build command inside [web-root]/docroot/themes/custom/yukonca_glider

Clear Drupal cache using drush cr

Drupal 10 website should be ready at this point


## Things to be confirmed post-migration ##


## Know Issues ##

1.. Some links on D7 production have inconsistent French translation.
https://yukon.ca/en/node/153

2.. Incorrect translation (English has French content and this exists on production as well):
https://yukon.ca/en/Dates-limites-des-depots-pour-municipalites


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


