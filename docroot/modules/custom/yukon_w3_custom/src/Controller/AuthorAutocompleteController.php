<?php

namespace Drupal\yukon_w3_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides autocomplete functionality for authors.
 */
class AuthorAutocompleteController extends ControllerBase {

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TableController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The pager manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Handles autocomplete requests.
   */
  public function handleAutocomplete(Request $request) {

    // Get the search term from the query string.
    $search = $request->query->get('q');
    $matches = [];

    if ($search) {
      // Query users with the role of 'author'.
      $query = $this->entityTypeManager->getStorage('user')->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 1)
        ->condition('name', '%' . $search . '%', 'LIKE')
        ->range(0, 10);

      // Execute the query.
      $uids = $query->execute();

      // Load user entities.
      $users = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);
      foreach ($users as $user) {
        $matches[] = [
          'value' => $user->getDisplayName(),
          'label' => $user->getDisplayName(),
        ];
      }
    }

    return new JsonResponse($matches);
  }

}
