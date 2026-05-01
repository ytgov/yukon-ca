<?php

namespace Drupal\yukon_w3_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\yukon_w3_custom\TranslationStatuses;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\NodeType;

/**
 * Provides route responses for content translation routing.
 */
class ContentTranslationController extends ControllerBase {
  use TranslationStatuses;

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
      ['data' => $this->t('Type & Department')],
      ['data' => $this->t('Translation Status')],
      ['data' => $this->t('Published Status')],
      ['data' => $this->t('<a href="' . $update_url . '">Updated</a>')],
      ['data' => $this->t('Operations')],
    ];

    $db = Database::getConnection();

    // Set the pager limit.
    $number_of_rows = $request->query->get('number_of_rows');
    $limit = !empty($number_of_rows) ? (int) $number_of_rows : 50;

    // Build the query once and reuse it for both the count and data fetch.
    $query = $db->select('node_field_data', 'ct')->distinct()
      ->fields('ct', ['nid', 'title', 'type', 'langcode', 'created', 'changed', 'uid', 'status'])
      ->condition('ct.langcode', 'en');

    $text = $request->query->get('filter_text');
    if (!empty($text)) {
      $query->condition('ct.title', '%' . $db->escapeLike($text) . '%', 'LIKE');
    }
    $type = $request->query->get('type');
    if (!empty($type) && $type != 'All') {
      $query->condition('ct.type', $type);
    }
    $published_status = $request->query->get('published_status');
    if (!empty($published_status) && $published_status != '') {
      $query->condition('ct.status', $published_status == 2 ? '0' : '1');
    }
    $author = $request->query->get('author');
    if (!empty($author) && $author != '') {
      $query_user = $this->entityTypeManager->getStorage('user')->getQuery()
        ->accessCheck(TRUE)
        ->condition('name', $author)
        ->range(0, 1);
      $uids = $query_user->execute();
      $uid = reset($uids);
      $query->condition('ct.uid', $uid);
    }
    $department = $request->query->get('department');
    if (!empty($department) && $department != '') {
      $query->join('node__field_department_term', 'nd', 'ct.nid = nd.entity_id');
      $query->condition('nd.field_department_term_target_id', $department);
    }

    $translation_status = $request->query->get('translation_status');
    if (!empty($translation_status)) {
      if (in_array($translation_status, ['in_progress', 'not_required'])) {
        $query->join('node__field_translation_status', 'ts', 'ct.nid = ts.entity_id');
        $query->condition('ts.field_translation_status_value', $translation_status);
      }
      else {
        // Computed statuses (absent/present/out_dated): exclude nodes with an
        // explicit in_progress or not_required value, then filter by French
        // translation existence and timestamp comparison.
        $no_explicit = $db->select('node__field_translation_status', 'ts2')
          ->fields('ts2', ['entity_id'])
          ->where('ts2.entity_id = ct.nid');
        $query->notExists($no_explicit);

        $fr = $db->select('node_field_data', 'fr')->fields('fr', ['nid'])
          ->where("fr.nid = ct.nid AND fr.langcode = 'fr'");

        if ($translation_status === 'absent') {
          $query->notExists($fr);
        }
        elseif ($translation_status === 'present') {
          $fr->where('ct.changed <= fr.changed');
          $query->exists($fr);
        }
        elseif ($translation_status === 'out_dated') {
          $fr->where('ct.changed > fr.changed');
          $query->exists($fr);
        }
      }
    }

    // Get accurate total from the filtered query, then add sort and pagination.
    $total = $query->countQuery()->execute()->fetchField();
    $current_page = $this->pagerManager->createPager($total, $limit)->getCurrentPage();
    if (!empty($sort) && $sort == 'asc') {
      $query->orderBy('ct.changed', 'ASC');
    }
    else {
      $query->orderBy('ct.changed', 'DESC');
    }
    $query->range($current_page * $limit, $limit);

    $results = $query->execute()->fetchAll();
    $rows = [];

    // Get labels for content types.
    $all_content_types = NodeType::loadMultiple();
    foreach ($all_content_types as $machine_name => $content_type) {
      $labels[$machine_name] = $content_type->label();
    }

    foreach ($results as $row) {
      $entity_id = $row->nid;
      $user_name = $this->get_username($row->uid);
      $department = $this->get_department($entity_id);

      $query1 = $db->select("node__field_translation_status", "n");
      $query1->fields("n", ['entity_id', 'field_translation_status_value']);
      $query1->condition("n.entity_id", $entity_id);
      $check_trans = $query1->execute()->fetchAll();

      $tr_row_status = !empty($check_trans) ? $check_trans[0]->field_translation_status_value : '';
      if (empty($check_trans)) {
        $query = $db->select("node_field_data", "n");
        $query->fields("n", ['nid', 'changed']);
        $query->condition("n.langcode", "fr");
        $query->condition("n.nid", $entity_id);
        $check_fr = $query->execute()->fetchAll();
        if (!empty($check_fr)) {
          $tr_row_status = 'present';
          if ($row->changed > $check_fr[0]->changed) {
            $tr_row_status = 'out_dated';
          }
        }
        else {
          $tr_row_status = 'absent';
        }
      }

      global $base_url;
      $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $row->nid);

      // Use the label if we know it, otherwise fall back to the machine name.
      if(!empty($labels[$row->type])) {
        $type = $labels[$row->type];
      } else {
        $type = $row->type;
      }

      $rows[] = [
        'data' => [
          Markup::create("<a href='" . $base_url . $alias . "'>" . $row->title . "</a><br><pre>" . $alias . "</pre>"),
          Markup::create($type . "<br>" . $department),
          $this->getTranslationStatusLabel($tr_row_status),
          $row->status ? $this->t('Published') : $this->t('Unpublished'),
          Markup::create(date('Y-m-d H:i a', $row->changed) . ($user_name[0] && $user_name[1] ? "<br><a href='" . $user_name[1]->alias . "'>" . $user_name[0]->name . "</a>" : "")),
          Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('entity.node.edit_form', ['node' => $row->nid])),
        ],
      ];
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

    if (!$rows) {
      return $build;
    }

    // Add a pager.
    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  function get_username($uid) {
    $db = Database::getConnection();
    $query = $db->select("users_field_data", "u");
    $query->fields("u", ['name', 'uid']);
    $query->condition("u.uid", $uid);
    $user[] = $query->execute()->fetchObject();

    if ($user[0]) {
      $query = $db->select("path_alias", "n");
      $query->condition("n.path", "/user/" . $user[0]->uid);
      $query->fields("n", ['alias']);
      $user[] = $query->execute()->fetchObject();
    }
    else {
      $user[] = FALSE;
    }

    return $user;
  }
  function get_department($nid) {
    $db = Database::getConnection();
    $query = $db->select("node__field_department_term", "n");
    $query->condition("n.entity_id", $nid);
    $query->join('taxonomy_term_field_data', 'nd', 'n.field_department_term_target_id = nd.tid');
    $query->fields("nd", ['name',]);
    $department = $query->execute()->fetchObject();
    return $department ? $department->name : '';
  }
}
