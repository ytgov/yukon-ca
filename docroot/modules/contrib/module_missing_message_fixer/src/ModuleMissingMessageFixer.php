<?php

namespace Drupal\module_missing_message_fixer;

use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Class ModuleMissingMessageFixer.
 *
 * @package Drupal\module_missing_message_fixer
 */
class ModuleMissingMessageFixer {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a new UserSelection object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(Connection $connection, MessengerInterface $messenger, ModuleExtensionList $extension_list_module) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->moduleExtensionList = $extension_list_module;
  }

  /**
   * {@inheritdoc}
   */
  public function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('extension.list.module')
    );
  }

  /**
   * Set the table headers for the ui and drush.
   *
   * @return string[]
   *   Format: $[$column_key] = $cell
   */
  public function getTableHeader() {
    return [
      'name' => 'Name',
      'type' => 'Type',
    ];
  }

  /**
   * Produces one table row for each missing module.
   *
   * The table rows are suitable for drush and for the admin UI.
   *
   * @return array[]
   *   Format: $[$extension_name][$column_key] = $cell
   */
  public function getTableRows() {
    // Initalize vars.
    $rows = [];

    // Grab all the modules in the system table.
    $results = $this->connection->select('key_value', 'k')
      ->fields('k', ['name'])
      ->condition('collection', 'system.schema')
      ->execute()
      ->fetchAll();

    // Go through the query and check the missing modules.
    // Plus do our checks to see what's wrong.
    foreach ($results as $record) {

      if ($record->name === 'default') {
        continue;
      }

      // Grab the checker.
      $filename = $this->moduleExtensionList->getPathname($record->name);

      if ($filename === NULL) {
        // Report this module in the table.
        $rows[$record->name] = [
          'name' => $record->name,
          'type' => 'module',
        ];
        continue;
      }

      $message = NULL;
      $replacements = [
        '@name' => $record->name,
        '@type' => 'module',
        '@file' => $filename,
      ];
      if (!file_exists($filename)) {
        // This case is unexpected, because drupal_get_filename() should take care
        // of it already.
        $message = 'The file @file for @name @type is missing.';
      }
      elseif (!is_readable($filename)) {
        // This case is unexpected, because drupal_get_filename() should take care
        // of it already.
        $message = 'The file @file for @name @type is not readable.';
      }
      else {
        // Verify if *.info file exists.
        // See https://www.drupal.org/node/2789993#comment-12306555
        $info_filename = dirname($filename) . '/' . $record->name . '.info.yml';
        $replacements['@info_file'] = $info_filename;
        if (!file_exists($info_filename)) {
          $message = 'The *.info.yml file @info_file for @name @type is missing.';

        }
        elseif (!is_readable($info_filename)) {
          $message = 'The *.info.yml file @info_file for @name @type is not readable.';
        }
      }

      if ($message !== NULL) {
        // This case should never occur.
        $this->messenger->addWarning(
          t($message, $replacements),
          FALSE);
      }
    }

    return $rows;
  }

}
