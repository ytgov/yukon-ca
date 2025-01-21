<?php

namespace Drupal\yukon_w3_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Pager\PagerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;

/**
 * Provides route responses for landing-page routing.
 */
class ContentTranslationController extends ControllerBase {
  /**
   * The pager manager service.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * Constructs a new TableController.
   *
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   The pager manager service.
   */
  public function __construct(PagerManagerInterface $pagerManager) {
    $this->pagerManager = $pagerManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('pager.manager')
    );
  }

  /**
   * Update the translation for primary links.
   */
  public function content() {
    $header = [
      ['data' => $this->t('ID'), 'field' => 'id'],
      ['data' => $this->t('Title'), 'field' => 'name'],
      ['data' => $this->t('Type')],
      ['data' => $this->t('language')],
      ['data' => $this->t('Operations')],
      ['data' => $this->t('Translation Status')],
    ];

    $query = Database::getConnection()->select('node_field_data', 'ct')
      ->fields('ct', ['nid', 'title', 'type', 'langcode'])
      ->condition('langcode', 'en');
    $results = $query->countQuery()->execute()->fetchField();

    // Set the pager limit.
    $limit = 50;
    $current_page = $this->pagerManager->createPager($results, $limit)->getCurrentPage();
    $offset = $current_page * $limit;

    // Query to fetch data from a database table.
    $db = Database::getConnection();
    $query = $db->select('node_field_data', 'ct')
      ->fields('ct', ['nid', 'title', 'type', 'langcode', 'created', 'changed'])
      ->condition('langcode', 'en')
      ->orderBy('nid', 'DESC')
      ->range($offset, $limit);
    $results = $query->execute()->fetchAll();

    $rows = [];
    foreach ($results as $row) {
      $entity_id = $row->nid;
      $query = $db->select("node_field_data", "n");
      $query->fields("n", ['nid', 'changed']);
      $query->condition("n.langcode", "fr");
      $query->condition("n.nid", $entity_id);
      $check_fr = $query->execute()->fetchAll();
      if (!empty($check_fr)) {
        $fr = "Present";
        if ($row->changed > $check_fr[0]->changed) {
          $fr = "Out-dated";
        }
      }
      else {
        $fr = "Absent";
      }
      $rows[] = [
        'data' => [
          $row->nid,
          $row->title,
          $row->type,
          $row->langcode,
          Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('entity.node.edit_form', ['node' => $row->nid])),
          $fr,
        ],
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No data found.'),
    ];

    // Add a pager.
    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

}
