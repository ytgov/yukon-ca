<?php

namespace Drupal\views_menu_children_filter\Plugin\views\argument;

use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views_menu_children_filter\MenuOptionsHelper;
use Drupal\views_menu_children_filter\Plugin\views\join\MenuChildrenNodeJoin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A filter to show menu children of a parent menu item.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("menu_children")
 */
class MenuChildren extends NumericArgument {

  /**
   * A join handler object.
   *
   * @var \Drupal\views_menu_children_filter\Plugin\views\join\MenuChildrenNodeJoin
   */
  protected $joinHandler;

  /**
   * A menu link manager object.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * MenuChildren constructor.
   *
   * @param \Drupal\views_menu_children_filter\Plugin\views\join\MenuChildrenNodeJoin $join_handler
   *   A join handler object.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   A menu link manager object.
   * @param array $configuration
   *   A configuration array.
   * @param string $plugin_id
   *   A plugin id.
   * @param mixed $plugin_definition
   *   A plugin definition.
   */
  public function __construct(MenuChildrenNodeJoin $join_handler, MenuLinkManagerInterface $menu_link_manager, array $configuration, $plugin_id, $plugin_definition) {
    $this->joinHandler = $join_handler;
    $this->menuLinkManager = $menu_link_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('views_menu_children_filter.join_handler'),
      $container->get('plugin.manager.menu.link'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    unset($options['not']);
    unset($options['break_phrase']);
    $options['target_menus'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    unset($form['not']);
    unset($form['break_phrase']);
    $form['target_menus'] = MenuOptionsHelper::getSelectField($this->options['target_menus']);
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $page_identifier = (is_array($this->value)) ? reset($this->value) : NULL;
    // If we aren't going to filter by a parent, just depend on the
    // join that is established in $this::setRelationship().
    if (!empty($page_identifier)) {
      $menus = $this->options['target_menus'];

      // Filter results to children nodes of the node found for the provided
      // page_identifier.
      $this->filterChildrenNodeByParent($this->query, $page_identifier, $menus);
    }
    // If 0 is passed through, we want top-level menu items only.
    elseif (is_null($page_identifier)) {
      $this->query->addWhere(0, 'menu_link_content_data.parent', NULL, 'IS NULL');
    }
  }

  /**
   * Filter results to child nodes of a MenuLink.
   *
   * Filter results to child nodes of a MenuLink found by the
   * $parent_page_identifier.
   *
   * @param \Drupal\views\Plugin\views\query\Sql $query
   *   The sql query.
   * @param string $parent_page_identifier
   *   String representation of a parent
   *   node to lookup. This could be a node ID or a relative URL to a parent
   *   page.
   * @param array $menus
   *   The menu names to constrain the results to.
   */
  public function filterChildrenNodeByParent(Sql $query, $parent_page_identifier, array $menus) {
    $url = self::getUrlFromFilterInput($parent_page_identifier);
    $link = $this->getMenuLinkFromTargetUrl($menus, $url);
    self::filterByPage($query, $link);
  }

  /**
   * Takes a url and finds the matching MenuLink within the provided menus.
   *
   * @param array|null $menus
   *   The menus to search for the parent within.
   * @param \Drupal\Core\Url $url
   *   The argument, usually a path or a NID.
   * @param bool $reset_cache
   *   Resets the static caching.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface|null
   *   The menu link within the provided menus.
   */
  public function getMenuLinkFromTargetUrl($menus, Url $url = NULL, $reset_cache = FALSE) {
    static $route_links = [];
    $route = NULL;
    $target_menu_link = NULL;

    if ($reset_cache) {
      $route_links = [];
    }

    if (empty($menus)) {
      $menus = ['all_menus'];
    }

    if (isset($url)) {
      $route = $url->getRouteName();
      $routeParameters = $url->getRouteParameters();

      // There is a strange behavior in looking up a link by route if a route
      // parameter only has a key, and no value. Check of the route parameters
      // has any keys with null values.
      foreach ($routeParameters as $key => $value) {
        if (is_null($value)) {
          unset($routeParameters[$key]);
        }
      }

      foreach ((array) $menus as $menu) {
        // Build an identifier of our values for static caching lookups.
        $path_identifier = self::buildRouteIdentifier($menu, $route, $routeParameters);

        // Has this lookup been performed and cached already?
        if (isset($route_links[$path_identifier])) {
          return $route_links[$path_identifier];
        }

        if ($menu === 'all_menus') {
          $menu = NULL;
        }

        $menu_links = $this->menuLinkManager->loadLinksByRoute($route, $routeParameters, $menu);

        if (!empty($menu_links)) {
          // Ideally, only one result is returned.
          // If multiple links returned, maybe I should add some
          // kind of reporting?
          // @todo Add alerting, throw exception,
          // or log the fact that more than one result was found.
          $target_menu_link = reset($menu_links);
          $route_links[$path_identifier] = $target_menu_link;
          break;
        }
      }
    }

    return $target_menu_link;
  }

  /**
   * Creates a string representing menu link for static caching.
   *
   * Example output: menu_name:route_name:route_param1_key:route_param1_value.
   *
   * @param string $menu_name
   *   The menu name.
   * @param string $route_name
   *   The route name.
   * @param array $route_parameters
   *   Example: [ 'node': 1 ].
   *
   * @return string
   *   A string representing menu link for static caching.
   */
  public static function buildRouteIdentifier($menu_name, $route_name, array $route_parameters) {
    // Merge the keys and values of the $route_parameters array into
    // a zipper like fashion.
    $zipped_arrays = array_map(NULL, array_keys($route_parameters), array_values($route_parameters));

    $parameters = '';
    if (!empty(reset($zipped_arrays))) {
      $parameters = implode(":", reset($zipped_arrays));
    }

    return "$menu_name:$route_name:" . $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function setRelationship() {
    $this->joinHandler->joinToNodeTable($this->query);
    $menus = $this->options['target_menus'];

    if ($menus) {
      $this->query->addWhereExpression(
        0,
        'menu_link_content_data.menu_name in (:menus[])',
        [':menus[]' => array_keys($menus)]
      );
    }
  }

  /**
   * Filter the query.
   *
   * Filter the query by either a: parent node, page page via its link_path, or
   * null and limit to root nodes.
   *
   * @param \Drupal\views\Plugin\views\query\Sql $query
   *   The $query The query
   *   we're going to alter.
   * @param \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent|null $link
   *   The by
   *   parent link.
   */
  public static function filterByPage(Sql $query, ?MenuLinkContent $link) {
    // Set $parent to 0 if no link was given to indicate no results:
    $parent = !empty($link)
      ? $link->getPluginId()
      : 0;

    $query->addWhereExpression(
      0,
      'menu_link_content_data.parent = :parent_lid',
      [':parent_lid' => $parent]
    );
  }

  /**
   * Gets a Url based on the provided user input.
   *
   * @param string|int $input
   *   The routable url path, or node ID. I.e.:
   *   node/100 (Supports an integer to default to node/%)
   *
   * @return \Drupal\Core\Url
   *   The Url object representing the parent entity.
   */
  protected static function getUrlFromFilterInput($input) {
    if (is_numeric($input)) {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $input]);
    }
    else {
      $url = Url::fromUserInput('/' . trim($input, '/'));
    }

    return $url;
  }

}
