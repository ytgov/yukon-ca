<?php

namespace Drupal\yukon_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d7\Node as D7Node;

/**
 * Customized source of Node migration to provide URL alias.
 *
 * @MigrateSource(
 *   id = "yukon_node"
 * )
 */
class Node extends D7Node {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');

    $query = $this->query()->fields('ps', ['pathauto'])
      ->condition('n.nid', $nid);
    $result = $query->execute()->fetchAssoc();
    if (isset($result['pathauto']) && $result['pathauto'] === "1") {
      $row->setSourceProperty('checked_alias', TRUE);
    }
    else {
      $row->setSourceProperty('checked_alias', FALSE);
    }

    $query = $this->select('url_alias', 'ua')->fields('ua', ['alias']);
    $query->condition('ua.source', 'node/' . $nid);
    $alias = $query->execute()->fetchField();
    if (!empty($alias)) {
      $row->setSourceProperty('alias', '/' . $alias);
    }

    // Get and set workbench moderation status.
    $vid = $row->getSourceProperty('vid');
    $row->setSourceProperty('moderation_state', $this->getWorkbenchModeration($nid, $vid));

    return parent::prepareRow($row);
  }

  /**
   * Get Workbench Moderation value.
   *
   * @param string $nid
   *   The node ID.
   * @param string $vid
   *   The node revision ID.
   *
   * @return array
   *   The workbench moderation value.
   */
  private function getWorkbenchModeration(string $nid, string $vid) {
    $workbench_moderation = $this->select('workbench_moderation_node_history', 'w')
      ->fields('w', [
        'from_state',
        'state',
      ])
      ->condition('vid', $vid)
      ->condition('nid', $nid)
      ->orderBy('stamp', 'DESC')
      ->execute()
      ->fetchAssoc();

    return [
      'current' => $workbench_moderation['state'],
      'state' => $workbench_moderation['from_state'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select node in its last revision.
    $query = parent::query();
    $query->leftJoin('pathauto_state', 'ps', '[n].[nid] = [ps].[entity_id]');
    return $query;
  }

}
