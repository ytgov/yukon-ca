# Views Menu Node Children Filter
The Views Menu Children Filter module adds a contextual filter to Views for
showing child nodes of a specified parent entity in the menu system. It also adds
a sorting option based on the menu link's weight.

For more informations on the module, visit the project page:
  http://drupal.org/project/views_menu_children_filter

To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/views_menu_children_filter

## Required Modules
- The Drupal Core "Views" module is needed.

## Installation
See regular module installation guide: http://drupal.org/node/1897420

### Composer & drush installation
- Run `composer require drupal/views_menu_children_filter`
- Run `drush en views_menu_children_filter`

## Configuration
- When you add the "Menu Children" contextual filter to your custom view, you
can configure the menus you wish to search for inside the filter settings.

## Limitations
- Currently this module only works for Node entities. Also, the menu links need
to be created inside the node, so they have the "entity:" uri prefix.
"internal:" is NOT currently supported.

## Example usage on showing children nodes on a node page.
- Create a view with the contextual "menu_children" filter and a view block.
- Specified contextual filter settings:
  - Hide view when filter IS NOT vailable.
  - Specify validation criteria: Content, Single ID, hide view if not validated
  when the filter IS available.
  - Target menus: Main navigation.
- Set View Block in footer and select "Node from URL" inside Block settings
"Content:Menu Children".
- Create a "main" node with a menu link under "Main Navigation".
- Create two nodes with menu links under our "main" node.
- Now when visiting the "main" node, you can see it's children in the footer
block!

## Troubleshooting

- If your menu children aren't showing up, check the following

  - Are you providing the correct parent ID in your contextual filter? The
  simplest way to do this is to select:

    "Provide default value" -> "Content ID from URL"

  - Is your view filtering based on content type? If so, does it have the
  appropriate settings to be able to show the child items? (Correct menu
  selected, correct Filter value settings e.g. validation criteria, correct
  block settings).
