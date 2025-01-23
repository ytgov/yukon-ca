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
    return new static(
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
    // Redirect to the same page with the filter applied.
    $form_state->setRedirect('yukon_w3_custom.content_translation', [], [
      'query' => ['filter_text' => $filter_text, 'type' => $type],
    ]);
  }

}
