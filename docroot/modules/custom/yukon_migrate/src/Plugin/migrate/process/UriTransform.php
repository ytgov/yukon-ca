<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a yukon_migrate_uri_transform plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: yukon_migrate_uri_transform
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "yukon_migrate_uri_transform"
 * )
 *
 * @DCG
 * ContainerFactoryPluginInterface is optional here. If you have no need for
 * external services just remove it and all other stuff except transform()
 * method.
 */
class UriTransform extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transformUri($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $value = str_ireplace('"https://www.yukon.ca', '"', $value);
    $value = str_ireplace('"http://www.yukon.ca', '"', $value);
    $value = str_ireplace('"https://yukon.ca', '"', $value);
    $value = str_ireplace('"http://yukon.ca', '"', $value);

    // [uuid-link:node:ba5dac36-a2a6-48df-a92c-dcba01cb40e5]
    $matches = [];
    while (preg_match('/\[uuid-link:node:([a-z0-9\-]+)\]/i', $value, $matches)) {

        $uuid = $matches[1];

        $node = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['uuid' => $uuid]);
        if ($node) {
          $value = str_ireplace($matches[0], '/node/' . $node->id(), $value);
        }
        else {
          $value = str_ireplace($matches[0], 'UUID_NOT_FOUND', $value);
        }

    }

    return $value;
  }

}
