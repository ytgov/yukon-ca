<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct.
   *
   * @param  array  $configuration
   *    Configuration.
   * @param $plugin_id
   *    The plugin id.
   * @param $plugin_definition
   *    The plugin defintion.
   * @param  \Drupal\Core\Entity\EntityTypeManagerInterface  $entityTypeManager
   *    The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

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
        $departmentTerm = $this->entityTypeManager
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
