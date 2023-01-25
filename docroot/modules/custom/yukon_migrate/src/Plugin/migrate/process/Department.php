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
 *   field_department_term:
 *     plugin: department
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "department",
 * )
 */
class Department extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $node = $row->getSource();
    $department = !empty($node['field_department_term']) ? $node['field_department_term'] : NULL;
    if (!empty($department)) {
      if (!is_array($department)) {
        $department = explode(',', $department);
      }
      $departmentData = [];

      foreach ($department as $tid) {
        $departmentTerm = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadByProperties([
            'tid' => $tid,
            'vid' => 'department',
          ]);
        $departmentTerm = reset($departmentTerm);

        $departmentData[] = ['target_id' => $departmentTerm->id()];
      }

      return $departmentData;
    }
  }

}
