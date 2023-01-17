<?php

namespace Drupal\single_content_sync\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\single_content_sync\Form\ContentExportForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Subscriber for single content sync routes.
 */
class ContentExportRoutes implements ContainerInjectionInterface {

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
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes(): array {
    $routes = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->hasLinkTemplate('single-content:export')) {
        $route = new Route(
          $entity_type->getLinkTemplate('single-content:export'),
          [
            '_form' => ContentExportForm::class,
            '_title' => 'Export',
          ],
          [
            '_entity_access' => "{$entity_type_id}.single-content:export",
            '_custom_access' => '\Drupal\single_content_sync\Form\ContentExportForm::access',
          ],
          [
            'parameters' => [
              $entity_type_id => [
                'type' => 'entity:' . $entity_type_id,
              ],
            ],
            '_admin_route' => TRUE,
          ]
        );

        $route_name = "entity.{$entity_type_id}.single_content:export";
        $routes[$route_name] = $route;
      }
    }

    return $routes;
  }

}
