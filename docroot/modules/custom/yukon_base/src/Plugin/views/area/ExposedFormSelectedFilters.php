<?php

namespace Drupal\yukon_base\Plugin\views\area;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Views area plugin to display selected filters via exposed form.
 *
 * @ViewsArea("exposed_form_selected_filters")
 */
class ExposedFormSelectedFilters extends AreaPluginBase {

  /**
   * Current path for the current request.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected CurrentPathStack $currentPath;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected ?Request $currentRequest;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentPathStack $current_path, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentPath = $current_path;
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.current'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['label'] = [
      'default' => '',
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->options['label'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $build = [];
    $items = [];

    /** @var \Drupal\views\ViewExecutable $view */
    $view = $this->view;
    $exposed_input = $view->getExposedInput();

    // Loop through exposed filters to build selected filters items.
    /** @var \Drupal\views\Plugin\views\ViewsHandlerInterface $handler */
    foreach ($view->filter as $handler) {
      // Consider only exposed filter.
      if ($handler->canExpose() && $handler->isExposed()) {
        $identifier = $handler->isAGroup() ? $handler->options['group_info']['identifier'] : $handler->options['expose']['identifier'];
        // Skip not-selected filters.
        if (empty($exposed_input[$identifier])) {
          continue;
        }
        // Value of the selected exposed filter.
        $exposed_item = $exposed_input[$identifier];
        // Build dummy exposed form for filter to get options.
        $dummy_form = [];
        $dummy_form_state = new FormState();
        if ($handler->isAGroup()) {
          $handler->groupForm($dummy_form, $dummy_form_state);
        }
        else {
          $handler->buildExposedForm($dummy_form, $dummy_form_state);
        }
        // Build selected filter items.
        // Consider that there can be multiple values of the exposed filter.
        if (is_array($exposed_item)) {
          foreach ($exposed_item as $item) {
            if ($item = $this->buildSelectedFilterItem($dummy_form, $identifier, $item)) {
              $items[] = $item;
            }
          }
        }
        else {
          if ($item = $this->buildSelectedFilterItem($dummy_form, $identifier, $exposed_item)) {
            $items[] = $item;
          }
        }
      }
    }

    if ($items) {
      $build['selected_filter'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => 'applied_filters',
        ],
        'label' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->options['label'],
        ],
        'items' => [
          '#theme' => 'item_list',
          '#items' => $items,
        ],
      ];
    }

    return $build;
  }

  /**
   * Build selected filter item.
   *
   * @param array $filter_form
   *   Exposed filter form.
   * @param string $identifier
   *   Filter identifier.
   * @param mixed $exposed_input_value
   *   Value of the exposed filter.
   *
   * @return \Drupal\Core\Link|null
   *   Selected filter item. It is link which will remove current exposed
   *   filter value.
   */
  protected function buildSelectedFilterItem(array $filter_form, string $identifier, mixed $exposed_input_value) {
    // Skip "any/all" value.
    if ($exposed_input_value == 'All') {
      return NULL;
    }

    $page_path = trim($this->currentPath->getPath(), '/');
    // All query parameters which are currently present at the page.
    $query_params = $this->currentRequest->query->all();
    // Get label of the selected filter item in case if it is select list,
    // otherwise use selected filter value.
    $title = $filter_form[$identifier]['#options'][$exposed_input_value] ?? $exposed_input_value;
    // Search for value of the exposed filter in query params and remove it to
    // prepare "remove filter" link.
    $params = $query_params;
    if (isset($params[$identifier])) {
      // Case when exposed filter can have multiple values.
      if (is_array($params[$identifier])) {
        $pos = array_search($exposed_input_value, $params[$identifier]);
        if ($pos !== 0) {
          unset($params[$identifier][$pos]);
        }
      }
      // Single value exposed filter.
      else {
        unset($params[$identifier]);
      }
    }

    $options = [
      'query' => $params,
      'attributes' => [
        'class' => ['btn', 'btn-default'],
        'type' => 'button',
      ],
    ];

    return Link::fromTextAndUrl($title, Url::fromUri('internal:/' . $page_path, $options));
  }

}
