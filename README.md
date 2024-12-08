# Yukon.ca

Drupal 10 Drupal build for Yukon.ca. This repository was prepared so it's sources can be built via CI to deploy to Pantheon.

[[_TOC_]]

## Local environment setup

For local environment setup, please check detailed instructions [here](LocalSetup.md).

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

## Setup Druapl 10 website

The first step of upgrading the Druapl 7 vesrion to Drupal 10 is setting up a blank Drupal 10 website by using the following steps:

1. Setup a web server
2. Copy the file system from the master branch
3. Run composer install
4. Create database and assign user
5. Enter the database credentails in the sites/default/settings.php file
```
  Automatically generated include for settings managed by ddev.
  $ddev_settings = dirname(__FILE__) . '/settings.ddev.php';
  if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
    require $ddev_settings;
 }
```
6. Hit the website URL to complete the installation process
7. Please note that while setting up the website, we need to select "minimal" profile. Selecting other profiles will generate issues.
8. Go to the terminal >> public_html and run this command to get the UUID (copy the UUID)

        ./vendor/bin/drush cget "system.site" uuid

9. Once UUID is copied, go to config/default/system.site.yml and the replace the existing with the copied one.
10. Go to the terminal >> public_html and run this command (select yes on prompt). Ignore the first error and run it again. After it gets completed, run it for the 3rd/4th time until only two files are left for the update.

        ./vendor/bin/drush cim

11. Clear cache >>  `./vendor/bin/drush cr`
12. Theme setup. Go to the terminal >> `cd public_html/docroot/themes/custom/yukonca_glider/`
13. Assuming that the correct node version is already installed run >> `npm run build`
14. Go to the terminal >> public_html and clear cache >>  `./vendor/bin/drush cr`
15. Import French translations by hitting <root domain>/admin/config/regional/translate/import. On this admim interface, select the fr.po file, select "French" in the dropdown, check the three boxes and click upload.
16. Clear cache

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


## Things to be confirmed post-migration ##

----------------------------------

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


