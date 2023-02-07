<?php

namespace Drupal\yukon_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class YGMigratePluginBase extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\migrate\Plugin\Migration
   */
  protected $migration;

  /**
   * Constructor.
   *
   * @phpstan-consistent-constructor
   *
   * @param array $configuration
   *    The config.
   * @param $plugin_id
   *    The plugin id.
   * @param $plugin_definition
   *    The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *    The migration process.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *    The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')
    );
  }

}