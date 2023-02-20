<?php

namespace Drupal\media_directories_ui\Plugin\EntityBrowser\WidgetValidation;

use Drupal\entity_browser\WidgetValidationBase;

/**
 * Validates that each passed Entity is of the correct type.
 *
 * @EntityBrowserWidgetValidation(
 *   id = "target_bundles",
 *   label = @Translation("Entity bundles validator"),
 *   data_type = "entity_reference",
 *   constraint = "Bundle"
 * )
 */
class TargetBundles extends WidgetValidationBase {}
