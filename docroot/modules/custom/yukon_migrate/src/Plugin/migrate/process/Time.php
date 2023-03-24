<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts time into seconds from midnight.
 *
 * Examples:
 *
 * @code
 * process:
 *   field_opening_time/value:
 *     plugin: yukon_migrate_time
 *     from_format: 'Y-m-d H:i:s'
 *     to_format: 'Y-m-d\TH:i:s'
 *     from_timezone: 'UTC'
 *     to_timezone: 'America/Whitehorse'
 *   field_closing_time/value:
 *     plugin: yukon_migrate_time
 *     from_format: 'Y-m-d H:i:s'
 *     to_format: 'Y-m-d\TH:i:s'
 *     from_timezone: 'UTC'
 *     to_timezone: 'America/Whitehorse'
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "yukon_migrate_time",
 * )
 */
class Time extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateException
   *   Migration exception.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['from_format'])) {
      throw new MigrateException('\'from_format\' must be set');
    }

    $from_format = $this->configuration['from_format'];
    $from_timezone = $this->configuration['from_timezone'] ?? NULL;
    $to_format = $this->configuration['to_format'];
    $to_timezone = $this->configuration['to_timezone'] ?? NULL;
    $settings = $this->configuration['settings'] ?? [];

    if (!empty($value)) {
      if ($from_format === 'Y-m-d\TH:i:s') {
        $value = str_replace(['-00-00T', '-00T'], ['-01-01T', '-01T'], $value);
      }
      $value = DateTimePlus::createFromFormat($from_format, $value, $from_timezone, $settings)->format($to_format, ['timezone' => $to_timezone]);
      if (strpos($value, ':48')) {
        $value = date('Y-m-d\TH:i:s', strtotime($value . ' +12 seconds'));
      }
      if (strpos($value, ':47:44') || strpos($value, ':17:44')) {
        $value = date('Y-m-d\TH:i:s', strtotime($value . ' +12 minutes'));
        $value = date('Y-m-d\TH:i:s', strtotime($value . ' +16 seconds'));
      }

      return $value;
    }
  }

}
