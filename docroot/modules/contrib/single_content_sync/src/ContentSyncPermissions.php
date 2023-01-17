<?php

namespace Drupal\single_content_sync;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentSyncPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ContentExportRoutes object.
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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns an array of entity type permissions for exporting content.
   *
   * @return array
   *   The entity type permissions
   */
  public function permissions(): array {
    $permissions = [];
    $entity_types = $this->entityTypeManager->getDefinitions();

    foreach ($entity_types as $entity_type) {
      if ($entity_type->hasLinkTemplate('single-content:export')) {
        $permissions["export {$entity_type->id()} content"] = [
          'title' => $this->t('%type_name: Export entity', [
            '%type_name' => $entity_type->getLabel(),
          ]),
        ];
      }
    }

    return $permissions;
  }

}
