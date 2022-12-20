# Glider: EvolvingWeb D8 Theme

* [Installation](#installation)
  * [Create Glider Sub-Theme](#create-glider-sub-theme)
  * [Setup Local Development](#setup-local-development)
* [Theme Overview](#overview)

---

## Installation

The Glider theme is set up to utilize the `base => sub-theme` relationship. The steps below will create your custom sub-theme that is cloned from `STARTERKIT/` folder, along with installing the proper dependencies to setup in a matter of minutes. This approach supports release updates, in addition to providing source references for required and optional pieces.

### Create Glider Sub-Theme

* In your `themes/` directory create the `contrib/` and `custom/` directories
* Download Glider into the `themes/contrib` folder (using composer).
* Create the sub-theme with `drush --include=docroot/themes/contrib/glider glider:subtheme my_theme`
* Enable your new `my_theme` theme with `drush en my_theme` which is located in `themes/custom`
* Set `my_theme` as your default theme `drush config-set system.theme default my_theme`

### Setup Local Development

Once you have created a custom sub-theme, you will setup for local compiling.

* Navigate to `themes/custom/mytheme` folder in your terminal
* Install Node.js with `./install-node.sh` and then point to the proper version with `source ~/.bashrc && nvm use --delete-prefix 12.20.0`
  * (optional) If you are not using avn then run `nvm use` when closing and reopening your session
* Run the command `npm install` within your `themes/custom/mytheme` folder
* Install the [Gulp](http://gulpjs.com/) build tool globally using `npm install -g gulp-cli`.
* To confirm Gulp and other items are instantiated `npm run build`
* You can now compile both your Sass and JS with `gulp watch`

## Theme Overview

For details document about Glider theme, go to 
[Glider document on XWiki](https://xwiki.ewdev.ca/xwiki/bin/view/Developers/Development_Processes/Glider_theme/)