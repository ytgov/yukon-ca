# Media directories

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Recommended modules
 * Inspiration
 * Roadmap
 * Maintainers


INTRODUCTION
------------

Provides directory structure for Media entities.
A new field will appear in every Media entity, which will allow assigning them to folders.
This module also offers an alternative way to browse and use Medias. Initial UI is available (full featured jsTree directory browser experience) and enhances the Media experience.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/media_directories

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/media_directories


REQUIREMENTS
------------

  * Drupal 9.3
  * Media (core)
  * Media library (core)
  * Entity browser (by submodule media_directories_ui)
  * Entity embed (by submodule media_directories_editor)
  * jsTree library (is taken from CDN as long as no min.js file exists), we recommend to install it manually (fe. to be able to work offline):
      1. Download jsTree from https://github.com/vakata/jstree
      2. Extract it as is, rename "jstree-X.Y.Z" to "jstree", so the assets are at:
        /libraries/jstree/dist/jstree.min.js

**Note:** jsTree library is ready to be managed via composer using the `wikimedia/composer-merge-plugin`.
Run `composer require wikimedia/composer-merge-plugin` and
Edit your site's `composer.json` file and add the following under the "extra" section:
```
"merge-plugin": {
   "include": [
      "web/modules/contrib/media_directories/composer.libraries.json"
   ]
},
```
Run `composer update --lock`


INSTALLATION
------------

We recommend composer to install the module, then normally enable the module in Drupal.
**media_directories_editor** will intsall everything,
**media_directories_ui** all the UX and field integration,
**media_directories** only the directory field and media(-library) integration.


CONFIGURATION
-------------

### Media directories module
 1. Create a vocabulary to hold directory structure.
 2. Select that vocabulary in the settings: /admin/config/media/media_directories

### Media directories UI module
 1. Use the entity browser Media Directory: Field widget form widget on your content types media reference fields
 2. Enable the media form display media_library (/admin/structure/media/manage/image/form-display) and limit the fields to configure a nice quick edit dialog.
 3. Optionally enable the combined upload form in the settings: /admin/config/media/media_directories

### Media directories editor module
Add the directory icon-ed Media button to a text format, fe. full_html (/admin/config/content/formats/manage/full_html) and make sure the "Embed media" and "Display embedded entities" filters are enabled.


RECOMMENDED MODULES
-------------------

Image resize filter for the editor integration.
https://www.drupal.org/project/image_resize_filter

SUPPORTED MODULES
-------------------

Admin toolbar can be configured to hide core's media overview, to only show our media browser.
https://www.drupal.org/project/admin_toolbar


INSPIRATION
-----------

Permissions by term permissions_by_entity sub-module (experimental) for a drag'n'drop media permission solution.
https://www.drupal.org/project/permissions_by_term


ROADMAP
-------

https://www.drupal.org/project/media_directories/issues/3102317


MAINTAINERS
-----------

 * ytsurk (https://www.drupal.org/user/1153644)
 * rang501 (https://www.drupal.org/user/235669)
