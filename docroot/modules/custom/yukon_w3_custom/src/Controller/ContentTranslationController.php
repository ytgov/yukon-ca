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
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Markup;

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
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TableController.
   *
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   The pager manager service.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The pager manager service.
   */
  public function __construct(PagerManagerInterface $pagerManager, FormBuilderInterface $formBuilder, RequestStack $request_stack, EntityTypeManagerInterface $entityTypeManager) {
    $this->pagerManager = $pagerManager;
    $this->formBuilder = $formBuilder;
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('pager.manager'),
      $container->get('form_builder'),
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Update the translation for primary links.
   */
  public function content() {
    $form = $this->formBuilder->getForm('Drupal\yukon_w3_custom\Form\SearchFilterForm');
    // Retrieve the current request.
    $request = $this->requestStack->getCurrentRequest();
    global $base_url;
    $sort = \Drupal::request()->get('sort');
    if (!empty($sort) && $sort == 'asc') {
      $update_url = $base_url . "/admin/content_translation?sort=desc";
    }
    else {
      $update_url = $base_url . "/admin/content_translation?sort=asc";
    }
    $header = [
      ['data' => $this->t('Title')],
      ['data' => $this->t('Type')],
      ['data' => $this->t('<a href="' . $update_url . '">Updated</a>')],
      ['data' => $this->t('Translation Status')],
      ['data' => $this->t('Operations')],
    ];

    $db = Database::getConnection();
    $query = $db->select('node_field_data', 'ct')->distinct()
      ->fields('ct', ['nid', 'title', 'type', 'langcode', 'changed'])
      ->condition('ct.langcode', 'en');
    $text = $request->query->get('filter_text');
    if (!empty($text)) {
      $query->condition("ct.title", '%' . $db->escapeLike($text) . '%', 'LIKE');
    }
    $type = $request->query->get('type');
    if (!empty($type) && $type != 'All') {
      $query->condition("ct.type", $type);
    }
    $published_status = $request->query->get('published_status');
    if (!empty($published_status) && $published_status != '') {
      $query->condition("ct.status", $published_status);
    }
    $author = $request->query->get('author');
    if (!empty($author) && $author != '') {
      // Use entity query to search for the user by name.
      $query_user = $this->entityTypeManager->getStorage('user')->getQuery()
        ->accessCheck(TRUE)
        ->condition('name', $author)
        ->range(0, 1);
      $uids = $query_user->execute();
      $uid = reset($uids);
      $query->condition("ct.uid", $uid);
    }
    $department = $request->query->get('department');
    if (!empty($department) && $department != '') {
      $query->join('node__field_department_term', 'nd', 'ct.nid = nd.entity_id');
      $query->condition("nd.field_department_term_target_id", $department);
    }
    $results = $query->countQuery()->execute()->fetchField();

    // Set the pager limit.
    $number_of_rows = $request->query->get('number_of_rows');
    if (!empty($number_of_rows)) {
      $limit = $number_of_rows;
    }
    else {
      $limit = 50;
    }
    $current_page = $this->pagerManager->createPager($results, $limit)->getCurrentPage();
    $offset = $current_page * $limit;

    // Query to fetch data from a database table.
    $query = $db->select('node_field_data', 'ct')->distinct()
      ->fields('ct', ['nid', 'title', 'type', 'langcode', 'created', 'changed']);
    $query->condition('ct.langcode', 'en');
    if (!empty($sort) && $sort == 'asc') {
        $query->orderBy('ct.changed', 'ASC');
    }
    else {
        $query->orderBy('ct.changed', 'DESC');
    }
    $query->range($offset, $limit);
    $text = $request->query->get('filter_text');
    if (!empty($text)) {
      $query->condition("ct.title", '%' . $db->escapeLike($text) . '%', 'LIKE');
    }
    $type = $request->query->get('type');
    if (!empty($type) && $type != 'All') {
      $query->condition("ct.type", $type);
    }
    $published_status = $request->query->get('published_status');
    if (!empty($published_status) && $published_status != '') {
      if ($published_status == 2) {
        $query->condition("ct.status", '0');
      }
      else {
        $query->condition("ct.status", '1');
      }
    }
    $author = $request->query->get('author');
    if (!empty($author) && $author != '') {
      // Use entity query to search for the user by name.
      $query_user = $this->entityTypeManager->getStorage('user')->getQuery()
        ->accessCheck(TRUE)
        ->condition('name', $author)
        ->range(0, 1);
      $uids = $query_user->execute();
      $uid = reset($uids);
      $query->condition("ct.uid", $uid);
    }
    $department = $request->query->get('department');
    if (!empty($department) && $department != '') {
      $query->join('node__field_department_term', 'nd', 'ct.nid = nd.entity_id');
      $query->condition("nd.field_department_term_target_id", $department);
    }
    $results = $query->execute()->fetchAll();
    $translation_status = $request->query->get('translation_status');
    $rows = [];

    foreach ($results as $row) {
      $entity_id = $row->nid;

      $query1 = $db->select("node__field_translation_status", "n");
      $query1->fields("n", ['entity_id', 'field_translation_status_value']);
      $query1->condition("n.entity_id", $entity_id);
      $check_trans = $query1->execute()->fetchAll();

      if (!empty($check_trans) && $check_trans[0]->field_translation_status_value == "in_progress") {
        $fr = "In-progress";
      }
      elseif (!empty($check_trans) && $check_trans[0]->field_translation_status_value == "not_required") {
        $fr = "Not-required";
      }
      else {
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
      }
      global $base_url;
      $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $row->nid);
      if (!empty($translation_status)) {
        if ($translation_status == strtolower($fr)) {
          $rows[] = [
            'data' => [
              Markup::create("<a href='" . $base_url . $alias . "'>" . $row->title . "</a><br>" . $alias),
              $row->type,
              date('Y-m-d H:i a', $row->changed),
              $fr,
              Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('entity.node.edit_form', ['node' => $row->nid])),
            ],
          ];
        }
      }
      else {
        $rows[] = [
          'data' => [
            Markup::create("<a href='" . $base_url . $alias . "'>" . $row->title . "</a><br>"  . $alias),
            $row->type,
            date('Y-m-d H:i a', $row->changed),
            $fr,
            Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('entity.node.edit_form', ['node' => $row->nid])),
          ],
        ];
      }
    }

    $build['filter_form'] = $form;

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No data found.'),
      '#attached' => [
        'library' => [
          'yukon_w3_custom/yukon_w3_custom.css',
        ],
      ],
    ];

    // Add a pager.
    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

}
