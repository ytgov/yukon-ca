<?php

namespace Drupal\yukon_migrate\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\NodeEntityTranslation;

/**
 * Drupal node entity translations source with addition language conditions.
 *
 * @MigrateSource(
 *   id = "yukon_node_entity_translation",
 *   source_module = "entity_translation"
 * )
 */
class YukonNodeEntityTranslation extends NodeEntityTranslation {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    return $query->where('[n].[language] <> [et].[language]');
  }

}
