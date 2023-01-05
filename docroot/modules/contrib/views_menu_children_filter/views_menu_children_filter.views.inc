<?php

/**
 * @file
 * An inc file containing system hooks.
 */

/**
 * Implements hook_views_data_alter().
 */
function views_menu_children_filter_views_data_alter(array &$data) {
  $entityTypes = ['node'];

  foreach ($entityTypes as $entityType) {
    $data[$entityType]['menu_children_filter'] = [
      'title' => t('Menu children'),
      'help' => t('Gets one level of child entities under the specified parent within the target menus.'),
      'argument' => [
        'id' => 'menu_children',
      ],
    ];

    $data[$entityType]['menu_children_sort'] = [
      'title' => t('Menu children weight'),
      'table' => [
        'group' => t('Menu Tree'),
        'base' => [
          'field',
        ],
      ],
      'help' => t("Sort by the menu children's weight."),
      'sort' => [
        'id' => 'menu_children',
      ],
    ];
  }

  // Adding sort handler to the global space for backward compatibility.
  $data['views']['menu_children'] = [
    'title' => t('Menu children weight (Deprecated)'),
    'table' => [
      'group' => t('Menu Tree'),
      'base' => [
        'field',
      ],
    ],
    'help' => t("Sort by the menu children's weight."),
    'sort' => [
      'id' => 'menu_children',
    ],
  ];
}
