<?php

namespace Drupal\media_directories\Plugin\views\filter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter media by directory.
 *
 * Overrides default 'All' option behaviour.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("media_directory")
 */
class MediaDirectory extends ManyToOne {

  /**
   * Stores the exposed input for this filter.
   *
   * @var array
   */
  public $validatedExposedInput = [];

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface
   */
  protected $vocabularyStorage;

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a MediaDirectory object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   * @param \Drupal\taxonomy\TermStorageInterface $term_storage
   *   The term storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory object.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VocabularyStorageInterface $vocabulary_storage, TermStorageInterface $term_storage, ConfigFactoryInterface $configFactory, EntityRepositoryInterface $entity_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->vocabularyStorage = $vocabulary_storage;
    $this->termStorage = $term_storage;
    $this->configFactory = $configFactory;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('taxonomy_vocabulary'),
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('config.factory'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $config = $this->configFactory->get('media_directories.settings');
    $vid = $config->get('directory_taxonomy');
    $this->options['vid'] = $vid;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $vocabulary = NULL;
    if (isset($this->options['vid'])) {
      $vocabulary = $this->vocabularyStorage->load($this->options['vid']);
    }

    if (empty($vocabulary)) {
      $settings_url = Url::fromRoute('media_directories.config_form');
      $form['markup'] = [
        '#markup' => '<div class="js-form-item form-item">' . $this->t('Vocabulary is not selected. Please select it in the <a href=":url">settings</a>.', [':url' => $settings_url->toString()]) . '</div>',
      ];

      // Initialize the form's value to avoid further errors.
      $form['value'] = [];
      return;
    }

    $tree = $this->termStorage->loadTree($vocabulary->id(), 0, NULL, TRUE);
    $options = [];

    if ($tree) {
      foreach ($tree as $term) {
        $choice = new \stdClass();
        $choice->option = [$term->id() => str_repeat('−', $term->depth + 1) . ' ' . $this->entityRepository->getTranslationFromContext($term)->label()];
        $options[] = $choice;
      }
    }

    $default_value = (array) $this->value;

    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];

      if (!empty($this->options['expose']['reduce'])) {
        $options = $this->reduceValueOptions($options);

        if (!empty($this->options['expose']['multiple']) && empty($this->options['expose']['required'])) {
          $default_value = [];
        }
      }

      if (empty($this->options['expose']['multiple'])) {
        if (empty($this->options['expose']['required']) && (empty($default_value) || !empty($this->options['expose']['reduce']))) {
          $default_value = 'All';
        }
        elseif (empty($default_value)) {
          $keys = array_keys($options);
          $default_value = array_shift($keys);
        }
        // Due to #1464174 there is a chance that array('')
        // was saved in the admin ui.
        // Let's choose a safe default value.
        elseif ($default_value == ['']) {
          $default_value = 'All';
        }
        else {
          $copy = $default_value;
          $default_value = array_shift($copy);
        }
      }
    }
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Select terms from vocabulary @voc', ['@voc' => $vocabulary->label()]),
      '#multiple' => TRUE,
      '#options' => $options,
      '#size' => min(9, count($options)),
      '#default_value' => $default_value,
    ];

    $user_input = $form_state->getUserInput();
    if ($exposed && isset($identifier) && !isset($user_input[$identifier])) {
      $user_input[$identifier] = $default_value;
      $form_state->setUserInput($user_input);
    }

    if (!$form_state->get('exposed')) {
      // Retain the helper option.
      $this->helper->buildOptionsForm($form, $form_state);

      // Show help text if not exposed to end users.
      $form['value']['#description'] = t('Leave blank for all. Otherwise, the first selected term will be the default instead of "Any".');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function exposedTranslate(&$form, $type) {
    parent::exposedTranslate($form, $type);

    if (isset($form['#type']) && $form['#type'] === 'select') {
      $config = $this->configFactory->get('media_directories.settings');

      if ($config->get('all_files_in_root')) {
        $form['#options']['All'] = $this->t('All directories');
      }
      else {
        $form['#options']['All'] = $this->t('Root directory');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $config = $this->configFactory->get('media_directories.settings');

    // If the value is 'All', then we show only elements with empty value.
    if (isset($this->validatedExposedInput[0]) && $this->validatedExposedInput[0] === 'All') {
      $new_group = $this->query->setWhereGroup('AND');
      $this->query->addWhereExpression($new_group, "$this->tableAlias.$this->realField IS NULL");

      if ($config->get('all_files_in_root')) {
        // Show everything.
        $this->query->setWhereGroup('OR', $new_group);
        $this->query->addWhereExpression($new_group, "$this->tableAlias.$this->realField IS NOT NULL");
      }
    }
    else {
      parent::query();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }
    // We need to know the operator, which is normally set in
    // \Drupal\views\Plugin\views\filter\FilterPluginBase::acceptExposedInput(),
    // before we actually call the parent version of ourselves.
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id']) && isset($input[$this->options['expose']['operator_id']])) {
      $this->operator = $input[$this->options['expose']['operator_id']];
    }

    // If view is an attachment and is inheriting exposed filters, then assume
    // exposed input has already been validated.
    if (!empty($this->view->is_attachment) && $this->view->display_handler->usesExposed()) {
      $this->validatedExposedInput = (array) $this->view->exposed_raw_input[$this->options['expose']['identifier']];
    }

    // If we're checking for EMPTY or NOT, we don't need any input, and we can
    // say that our input conditions are met by just having the right operator.
    if ($this->operator == 'empty' || $this->operator == 'not empty') {
      return TRUE;
    }

    $rc = parent::acceptExposedInput($input);
    $value = $input[$this->options['expose']['identifier']];

    // Change default behaviour, we need to filter 'All' values.
    if (!$rc && $value === 'All') {
      $rc = TRUE;
      $this->validatedExposedInput = [$value];
    }

    if ($rc) {
      // If we have previously validated input, override.
      if (isset($this->validatedExposedInput)) {
        $this->value = $this->validatedExposedInput;
      }
    }

    return $rc;
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    $identifier = $this->options['expose']['identifier'];

    if ($form_state->getValue($identifier) != 'All') {
      $this->validatedExposedInput = (array) $form_state->getValue($identifier);
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    // Prevent array_filter from messing up our arrays in parent submit.
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);

    $form['error_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display error message'),
      '#default_value' => !empty($this->options['error_message']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    // Set up $this->valueOptions for the parent summary.
    $this->valueOptions = [];

    if ($this->value) {
      $this->value = array_filter($this->value);
      $terms = Term::loadMultiple($this->value);
      foreach ($terms as $term) {
        $this->valueOptions[$term->id()] = $this->entityRepository->getTranslationFromContext($term)->label();
      }
    }
    return parent::adminSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    // The result potentially depends on term access and so is just cacheable
    // per user.
    // @todo See https://www.drupal.org/node/2352175.
    $contexts[] = 'user';

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $vocabulary = NULL;
    if (isset($this->options['vid'])) {
      $vocabulary = $this->vocabularyStorage->load($this->options['vid']);
    }
    if ($vocabulary) {
      $dependencies[$vocabulary->getConfigDependencyKey()][] = $vocabulary->getConfigDependencyName();
    }

    foreach ($this->termStorage->loadMultiple($this->options['value']) as $term) {
      $dependencies[$term->getConfigDependencyKey()][] = $term->getConfigDependencyName();
    }

    return $dependencies;
  }

}
