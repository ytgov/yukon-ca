<?php

namespace Drupal\single_content_sync\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the local tasks for all content entity types.
 */
class ContentExportLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ContentExportLocalTasks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type->hasLinkTemplate('single-content:export')) {
        $derivative_id = "single_content_sync.{$entity_type->id()}_export";
        $this->derivatives[$derivative_id] = [
          'route_name' => "entity.{$entity_type->id()}.single_content:export",
          'base_route' => "entity.{$entity_type->id()}.canonical",
          'title' => 'Export',
          'weight' => 10,
        ];
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
