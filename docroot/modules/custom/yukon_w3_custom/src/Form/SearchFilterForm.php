<?php

namespace Drupal\yukon_w3_custom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides seach filter form.
 */
class SearchFilterForm extends FormBase {
  /**
   * The pager manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The RequestStack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new TableController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The pager manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RequestStack $request_stack) {
    $this->entityTypeManager = $entityTypeManager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the current request.
    $request = $this->requestStack->getCurrentRequest();
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    $output = ["All" => 'All'];
    foreach ($content_types as $type) {
      $output[$type->id()] = $type->label();
    }

    $form['filter_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $request->query->get('filter_text'),
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $output,
      '#default_value' => $request->query->get('type'),
    ];
    $published_status = ['' => 'Any', '1' => 'Published', '2' => 'Unpublished'];
    $form['published_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Published status'),
      '#options' => $published_status,
      '#default_value' => $request->query->get('published_status'),
    ];
    $term_data = ['' => 'Any'];
    $vid = 'department';
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      $term_data[$term->tid] = $term->name;
    }
    $form['department'] = [
      '#type' => 'select',
      '#title' => $this->t('Department'),
      '#options' => $term_data,
      '#default_value' => $request->query->get('department'),
    ];
    $form['author'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Author'),
      '#autocomplete_route_name' => 'yukon_w3_custom.author_autocomplete',
      '#description' => $this->t('Start typing to find an author.'),
      '#default_value' => $request->query->get('author'),
    ];
    $translation_status = [
      '' => 'Any',
      'absent' => 'Absent',
      'in-progress' => 'In-progress',
      'present' => 'Present',
      'out-dated' => 'Out-dated',
      'not-required' => 'Not-required',
    ];
    $form['translation_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Translation Status'),
      '#options' => $translation_status,
      '#default_value' => $request->query->get('translation_status'),
    ];
    $number_of_rows = ['50' => '50', '100' => '100', '200' => '200', '500' => '500'];
    $form['number_of_rows'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of rows'),
      '#options' => $number_of_rows,
      '#default_value' => $request->query->get('number_of_rows'),
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply Filter'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $filter_text = $form_state->getValue('filter_text');
    $type = $form_state->getValue('type');
    $published_status = $form_state->getValue('published_status');
    $department = $form_state->getValue('department');
    $author = $form_state->getValue('author');
    $translation_status = $form_state->getValue('translation_status');
    $number_of_rows = $form_state->getValue('number_of_rows');
    // Redirect to the same page with the filter applied.
    $form_state->setRedirect('yukon_w3_custom.content_translation', [], [
      'query' => [
        'filter_text' => $filter_text,
        'type' => $type,
        'published_status' => $published_status,
        'department' => $department,
        'author' => $author,
        'translation_status' => $translation_status,
        'number_of_rows' => $number_of_rows,
      ],
    ]);
  }

}
