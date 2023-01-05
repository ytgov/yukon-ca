<?php

namespace Drupal\cshs\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\cshs\CshsOptionsFromHelper;
use Drupal\cshs\Element\CshsElement;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The common implementation for the `CshsTaxonomyIndex` plugin.
 */
trait CshsTaxonomyIndex {

  use CshsOptionsFromHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    \assert($instance instanceof self);
    $instance->entityRepository = $container->get('entity.repository');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL): void {
    $options['value'] = isset($options['value']) ? (array) $options['value'] : [];

    parent::init($view, $display, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions(): array {
    $options = parent::defineOptions();

    foreach (static::defaultSettings() + ['type' => CshsElement::ID] as $option => $value) {
      $options[$option] = ['default' => $value];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildExtraOptionsForm($form, $form_state);

    $form['type']['#options'] += [
      CshsElement::ID => $this->t('Client-side Hierarchical Select'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state): void {
    parent::buildExposeForm($form, $form_state);

    if (CshsElement::ID === $this->options['type']) {
      // Disable the "multiple" option in the exposed form settings.
      $form['expose']['multiple']['#access'] = FALSE;
      $form += $this->settingsForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state): void {
    parent::valueForm($form, $form_state);

    if ($this->options['limit'] && empty($this->getVocabularies())) {
      $form['markup'] = [
        '#type' => 'item',
        '#markup' => $this->t('An invalid vocabulary is selected. Please change it in the options.'),
      ];
    }
    elseif (CshsElement::ID === $this->options['type']) {
      $form['value'] = \array_merge($form['value'], $this->formElement(), [
        '#multiple' => FALSE,
        '#default_value' => (array) $form['value']['#default_value'],
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key): mixed {
    return $this->options[$key] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getVocabularyStorage(): VocabularyStorageInterface {
    return $this->vocabularyStorage;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTermStorage(): TermStorageInterface {
    return $this->termStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabulariesIds(): array {
    return [$this->options['vid']];
  }

  /**
   * Returns the views filter configuration schema.
   *
   * @return array
   *   The config schema, provided in addition to the parent implementation.
   *
   * @see taxonomy.views.schema.yml
   * @see \cshs_config_schema_info_alter()
   */
  public static function getConfigSchema(): array {
    return [
      'save_lineage' => [
        'type' => 'boolean',
        'label' => 'Save lineage',
      ],
      'force_deepest' => [
        'type' => 'boolean',
        'label' => 'Force selection of deepest level',
      ],
      'parent' => [
        'type' => 'integer',
        'label' => 'Parent',
      ],
      'level_labels' => [
        'type' => 'string',
        'label' => 'Labels per hierarchy-level',
      ],
      'hierarchy_depth' => [
        'type' => 'integer',
        'label' => 'Hierarchy depth',
      ],
      'required_depth' => [
        'type' => 'integer',
        'label' => 'Required depth',
      ],
      'none_label' => [
        'type' => 'string',
        'label' => 'The "no selection" label',
      ],
    ];
  }

}
