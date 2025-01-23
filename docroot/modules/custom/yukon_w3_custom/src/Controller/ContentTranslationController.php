<?php

namespace Drupal\yukon_w3_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Pager\PagerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides route responses for content translation routing.
 */
class ContentTranslationController extends ControllerBase {
  /**
   * The pager manager service.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The RequestStack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new TableController.
   *
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   The pager manager service.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(PagerManagerInterface $pagerManager, FormBuilderInterface $formBuilder, RequestStack $request_stack) {
    $this->pagerManager = $pagerManager;
    $this->formBuilder = $formBuilder;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('pager.manager'),
      $container->get('form_builder'),
      $container->get('request_stack')
    );
  }

  /**
   * Update the translation for primary links.
   */
  public function content() {
    $form = $this->formBuilder->getForm('Drupal\yukon_w3_custom\Form\SearchFilterForm');
    // Retrieve the current request.
    $request = $this->requestStack->getCurrentRequest();
    $header = [
      ['data' => $this->t('ID')],
      ['data' => $this->t('Title')],
      ['data' => $this->t('Type')],
      ['data' => $this->t('language')],
      ['data' => $this->t('Operations')],
      ['data' => $this->t('Translation Status')],
    ];

    $db = Database::getConnection();
    $query = $db->select('node_field_data', 'ct')
      ->fields('ct', ['nid', 'title', 'type', 'langcode'])
      ->condition('langcode', 'en');
    $text = $request->query->get('filter_text');
    if (!empty($text)) {
      $query->condition("ct.title", '%' . $db->escapeLike($text) . '%', 'LIKE');
    }
    $type = $request->query->get('type');
    if (!empty($type) && $type != 'All') {
      $query->condition("ct.type", $type);
    }
    $results = $query->countQuery()->execute()->fetchField();

    // Set the pager limit.
    $limit = 50;
    $current_page = $this->pagerManager->createPager($results, $limit)->getCurrentPage();
    $offset = $current_page * $limit;

    // Query to fetch data from a database table.
    $query = $db->select('node_field_data', 'ct')
      ->fields('ct', ['nid', 'title', 'type', 'langcode', 'created', 'changed'])
      ->condition('langcode', 'en')
      ->orderBy('nid', 'DESC')
      ->range($offset, $limit);
    $text = $request->query->get('filter_text');
    if (!empty($text)) {
      $query->condition("ct.title", '%' . $db->escapeLike($text) . '%', 'LIKE');
    }
    $type = $request->query->get('type');
    if (!empty($type) && $type != 'All') {
      $query->condition("ct.type", $type);
    }
    $results = $query->execute()->fetchAll();

    $rows = [];
    foreach ($results as $row) {
      $entity_id = $row->nid;
      $query = $db->select("node_field_data", "n");
      $query->fields("n", ['nid', 'changed']);
      $query->condition("n.langcode", "fr");
      $query->condition("n.nid", $entity_id);
      $text = $request->query->get('filter_text');
      if (!empty($text)) {
        $query->condition("n.title", '%' . $db->escapeLike($text) . '%', 'LIKE');
      }
      $type = $request->query->get('type');
      if (!empty($type) && $type != 'All') {
        $query->condition("n.type", $type);
      }
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

    $build['filter_form'] = $form;

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
