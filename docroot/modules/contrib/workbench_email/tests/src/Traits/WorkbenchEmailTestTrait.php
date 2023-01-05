<?php

namespace Drupal\Tests\workbench_email\Traits;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Contains helper classes for tests to set up various configuration.
 */
trait WorkbenchEmailTestTrait {

  /**
   * Adds an email field to a node bundle.
   *
   * @param string $bundle
   *   (optional) Bundle name. Defaults to 'test'.
   */
  protected function setUpEmailFieldForNodeBundle($bundle = 'test') {
    // Add an email field notify to the bundle.
    FieldStorageConfig::create([
      'cardinality' => 1,
      'entity_type' => 'node',
      'field_name' => 'field_email',
      'type' => 'email',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_email',
      'bundle' => 'test',
      'label' => 'Notify',
      'entity_type' => 'node',
    ])->save();
    if (!$entity_form_display = EntityFormDisplay::load(sprintf('node.%s.default', $bundle))) {
      $entity_form_display = EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => $bundle,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $entity_form_display->setComponent('field_email', ['type' => 'email_default'])->save();
  }

}
