<?php

namespace Drupal\yukon_migrate\Drush\Commands;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\migrate_tools\DrushLogMigrateMessage;
use Symfony\Component\Console\Output\OutputInterface;

class YukonDrushLogMigrateMessage extends DrushLogMigrateMessage {

  protected OutputInterface $output;

  function __construct(OutputInterface $output) {
    $this->output = $output;
    $this->logger = \Drupal::service('logger.channel.migrate_tools');
  }

  /**
   * Output a message from the migration.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The type of message to display.
   *
   * @see drush_log()
   */
  public function display($message, $type = 'status'): void {
    $type = $this->map[$type] ?? RfcLogLevel::NOTICE;
    $this->logger->log($type, $message);
    $this->output->writeln($message);
  }
}
