<?php

namespace Drupal\views_menu_children_filter\Plugin\views\join;

use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\views\Plugin\views\query\Sql;

/**
 * Views Join plugin to join the Node table to the menu_tree table.
 *
 * @package Drupal\views_menu_children_filter
 * @ingroup views_join_handlers
 * @ViewsJoin("menu_children_node_join")
 */
class MenuChildrenNodeJoin extends JoinPluginBase {

  /**
   * DB prefixes.
   *
   * The values to concat with node.nid in to join on the menu_tree's
   * route_param_key column.
   *
   * @var arrayDefaultsto[entitynode]
   */
  public $prefixes = ['entity:node/'];

  /**
   * Creates an instance of the plugin.
   *
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
  public static function create(array $configuration, $plugin_id, $plugin_definition) {
    // Setup some defaults.
    $configuration = array_merge([
      'type' => 'INNER',
      'table' => 'menu_link_content_data',
      // Formula value handled in the "buildJoin" function.
      'field' => FALSE,
      'left_table' => FALSE,
      'left_field' => FALSE,
      'operator' => '=',
    ], $configuration);

    $plugin_id = empty($plugin_id)
      ? "menu_children_node_join"
      : $plugin_id;

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildJoin($select_query, $table, $view_query) {
    $values = [];
    $node_table_alias = $select_query->getTables()['node_field_data']['alias'];

    $condition_parts = [];
    foreach ($this->prefixes as $prefix) {
      // Concatenate prefix with node.nid and provide as a parametrized value.
      $placeholder = ':views_join_condition_' . $select_query->nextPlaceholder();
      $values[$placeholder] = $prefix;
      $condition_parts[] = "( CONCAT($placeholder, $node_table_alias.nid) = $this->table.link__uri)";
    }

    $condition = sprintf(
      '(%s.enabled = 1) AND (%s)',
      $this->table,
      implode(' OR ', $condition_parts)
    );

    $select_query->addJoin($this->type, $this->table, $table['alias'], $condition, $values);
  }

  /**
   * Adds a query to the join.
   *
   * @param \Drupal\views\Plugin\views\query\Sql $query
   *   The query that the join will be added to.
   * @param bool $allow_duplicate_join
   *   If "false", prevents this join from joining more than once if this
   *   function is called repeatedly.
   */
  public function joinToNodeTable(Sql $query, $allow_duplicate_join = FALSE) {
    // Because this can be called from the argument and sort handlers,
    // first check to see if the join has already been applied.
    if (!$allow_duplicate_join && isset($query->tables['node_field_data']['menu_link_content_data'])) {
      return;
    }

    $query->queueTable("menu_link_content_data", "node_field_data", $this);
  }

  /**
   * Filter the query.
   *
   * Filter the query by either a: parent node, page page via its link_path,
   * or null and limit to root nodes.
   *
   * @param \Drupal\views\Plugin\views\query\Sql $query
   *   The $query The query we're going to alter.
   * @param \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $link
   *   The by parent link.
   */
  public static function filterByPage(Sql $query, MenuLinkContent $link) {
    $parent = $link->getPluginId();

    $query->addWhereExpression(
      0,
      'menu_link_content_data.parent = :parent_lid',
      [':parent_lid' => $parent]
    );
  }

}
