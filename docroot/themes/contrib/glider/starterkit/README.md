# Glider Starter Kit

This starter kit helps you setup a theme for a specific project.

## Theme inheritance

Here's a list to explain the theme inheritance.

1. Classy
2. Glider (Evolving Web)
3. Project theme (The theme you'll create using the `STARTERKIT`)

## Create project theme

To create a theme for your project, do the following steps:

  1. Choose a machine-name for your theme. Example: `dodgy_dozer`.

      * Preferably, this should be less than 16 characters long.

      * It should start with an alphabet and can contain alphabets, numbers and
        / or underscores.

  1. Copy this `STARTERKIT` directory and paste it into
     `DRUPAL/themes/contrib/THEME-NAME`.

       * Replace `THEME-NAME` with the machine-name you chose in step 1.

  1. Rename the following files to replace the word `STARTERKIT` to your
     theme's machine-name.

     a. `STARTERKIT.info.yml`

     b. `STARTERKIT.libraries.yml`

  1. Edit the `STARTERKIT.info.yml` to update the following items:

     a. `name` should contain a human-readable name for your theme. Example:
        `Flappy Wings`

     a. Replace the word `STARTERKIT` in with your theme's machine name.

     a. Remove the line that reads `hidden: true` – without this, your theme
        will not be visible on the *Appearance* settings page in the Drupal UI.

   1. The last step is to enable your theme from the `admin/appearance` page.

   1. You can optionally create a `16:9` image containing a screenshot of the
      way your website looks and place it as `screenshot.png` in your theme's
      folder.

## Compiling CSS / JS

The Glider theme and all it's sub-themes are designed to use `gulp` to compile
SASS and JavaScript. Here's how you can get the ball rolling:

### One-time setup

  1. Install `nvm` if it is not already installed. This helps you manage
     multiple versions of Node JS.

  1. Open the terminal and `cd` into your theme's directory.

  1. Install the Node version `16.x`. You can do this by running
     `./install-node.sh 16.14.2`. You only need to do this once.

  1. The previous step will generate a `.nvmrc` file. To use the correct
     version of node for this project, simply `cd` into your theme's directory
     and run `nvm use`.

  1. Next, we install `gulp`. Gulp will help us compile SASS / JS files. Simply
     run `npm install` and all the right tools and libraries will be installed.

### Compiling CSS / JS

First you have to install `nvm` and `gulp` as per instructions above. Once that
is done, you should be able to run `nvm --version` and `gulp --version` and
see their respective versions without any error.

Now whenever you want to work with SASS / JS, do the following:

  1. Open a terminal window and `cd` into your theme's directory.

  1. Run `nvm use` to make sure that you're using the right version of Node.

  1. Run `gulp watch` and it will watch your SCSS / JS files for changes. As
     and when changes are detected, the respective CSS / JS files will be
     compiled and placed in the `css` and the `js` directories respectively.

You can then test your CSS / JS changes and if you're happy with them, you can
commit them into the version control system.
