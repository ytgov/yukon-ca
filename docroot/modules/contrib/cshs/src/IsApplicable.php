<?php

namespace Drupal\cshs;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides the `isApplicable` implementation for formatters and widgets.
 */
trait IsApplicable {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    return 'taxonomy_term' === $field_definition->getFieldStorageDefinition()->getSetting('target_type');
  }

}
