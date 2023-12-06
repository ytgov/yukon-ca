# Developing locally

This document will walk you through the process of setting up the project locally.


## Quick start

If you already have Docksal installed and the EW docksal stacks installed, just run:

   ```
   $ fin init
   $ fin setup
   ```

To use composer and drush within the container (recommended):

  ```
  $ fin composer
  $ fin drush
  ```

## Local Dev Environment Setup

### Pantheon access

**This setup uses Drush to synchronize DB, make sure you have access to the Yukon D10 Pantheon site, add you ssh key and have the token to use terminus on your local environment https://dashboard.pantheon.io/personal-settings/machine-tokens, and https://dashboard.pantheon.io/personal-settings/ssh-keys.**

### Install Docksal

This setup uses docksal, ensure docksal is installed on you development machine. You can find instructions in https://docksal.io/installation

### Add EW custom Docksal stacks

Head to the repository to fetch our custom Docksal stacks : https://gitlab.ewdev.ca/universe/docksal

Stack configuration inside the stacks folder should be copied/linked in ~/.docksal/stacks.

### Spin up environment

To initialise the project, run

  ```
  $ fin init
  ```
The init command downloads the required docker images, and starts a project with the provided configuration.

To check the project configuration run fin config. Also check docksal.yml and docksal.env in the .docksal folder.

After init completes without any errors, run
  ```
  $ fin setup
  ```
The setup command will do the following:

Uses package manager composer to download all the required drupal packages and their dependencies.

Runs blt:source to create the local environment configuration.

Runs nvm to install the correct node version as the themes .nvmrc.

Installs front end dependencies using yarn.

Compiles SCSS and JS using gulp.

Downloads and imports database from Pantheon.

And finally opens the application in web browser.

### Available commands

 List of available commands

  ```
  $ fin
  ```
This will show you a long list of all commands available for Docksal and available custom commands. For more extensive details on what these commands do please read Docksal's documentation.


### Most commonly used custom commands

Sync most recent database

 To pull latest database from hosted environment using Drush aliases:

$ fin sync db [env]

The environment values can be dev, test, prod Or any other configured drush alias.

Sync most recent files

 To pull files locally from hosted environment using Drush aliases:

 $ fin sync files [env]

The environment values can be dev, test, prod Or any other configured drush alias.

### Frontend

To compile SCSS and JS.

 $ fin gulp

Watch and compile the SCSS and JS files.

$ fin gulp watch

Install a new dependency using yarn.

$ fin yarn install [package]

### To Manually Import DB

Just in case you have to manually import the database dump.

$ fin db import uncompressed_db_dump.sql

Note: If after importing database you are still getting Drupal install screen then check the existence of web/sites/default/settings.local.php file and if it not exists then create it from default.settings.local.php file.

### Open Drupal application in browser

$ fin open

### Open PhpMyAdmin.

$ fin open pma

### Open Solr backend web interface.

$ fin open solr

### Open MailHog.

$ fin open mailhog

or

$ fin open mail

### Generate a one-time link

$ fin drush uli

Login into the container


## BLT

BLT (Build and Launch Tool) provides an automation layer for testing, building, and launching Drupal 8 and 9 applications.

$ fin blt [command]

fin blt

Displays all the available BLT commands.

The blt configuration is stored in blt folder at the root of project codebase.

# Known issues

 - For mac Creating a container with the port 53 fails with the error address already in use. As a workaround, deactivate network acceleration by adding "kernelForUDP": false, in the settings.json file located at ~/Library/Group Containers/group.com.docker/settings.json.
 - Fin setup can fail, you can see the command here: .docksal/commands/setup you can execute the next command on the list to restart the process without re-run everything again, for example, if the process stop on the gulp lint, you can continue importing the db with `fin import db` and then you can sync the files with `fin sync files`, those sync process are using dev by default, but you can sync with an environment using command parameter like `fin sync files test` `fin sync db test`.

# Resources

 - Docksal Commands - https://docs.docksal.io/fin/fin-help/
 - Docker known issues - https://docs.docker.com/desktop/release-notes/#known-issues
