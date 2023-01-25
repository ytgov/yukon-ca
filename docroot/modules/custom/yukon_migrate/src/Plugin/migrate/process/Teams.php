<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Adds taxonomy terms to node content.
 *
 * Example:
 *
 * @code
 * process:
 *   field_yukon_editorial_team:
 *     plugin: teams
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "teams",
 * )
 */
class Teams extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $node = $row->getSource();
    $teams = !empty($node['field_yukon_editorial_team']) ? $node['field_yukon_editorial_team'] : NULL;
    if (!empty($teams)) {
      if (!is_array($teams)) {
        $teams = explode(',', $teams);
      }
      $teamData = [];

      foreach ($teams as $tid) {
        $teamTerm = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadByProperties([
            'tid' => $tid,
            'vid' => 'teams',
          ]);
        $teamTerm = reset($teamTerm);

        $teamData[] = ['target_id' => $teamTerm->id()];
      }

      return $teamData;
    }
  }

}
