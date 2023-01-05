<?php

namespace Drupal\views_menu_children_filter;

use Drupal\system\Entity\Menu;

/**
 * Provides helper classes for getting an options array of menus.
 *
 * @package Drupal\views_menu_children_filter
 */
class MenuOptionsHelper {

  /**
   * Gets an array of all menu names.
   *
   * @return array
   *   An array of all menu names.
   */
  public static function getMenuNames() {
    $menus = [];

    /** @var \Drupal\Core\Entity\EntityInterface $menu */
    foreach (Menu::loadMultiple() as $menu) {
      $menus[$menu->id()] = $menu->label();
    }

    asort($menus);

    return $menus;
  }

  /**
   * Gets a list of menus to display as select options.
   *
   * @return array
   *   An list of menus to display as select options.
   */
  public static function getMenuOptions() {
    return ['' => t('-- Select menu --')] + self::getMenuNames();
  }

  /**
   * Gets a select field definition for selecting target menus.
   *
   * @param array $defaultValue
   *   The form's default value.
   *
   * @return array
   *   A select field definition render array.
   */
  public static function getSelectField(array $defaultValue = []) {
    return [
      '#type' => 'select',
      '#title' => t('Target menus'),
      '#description' => t('Select the menu(s) to scan for child entities. If no menu is selected, the first matching menu will be used.'),
      '#multiple' => TRUE,
      '#options' => self::getMenuOptions(),
      '#default_value' => $defaultValue,
    ];
  }

}
