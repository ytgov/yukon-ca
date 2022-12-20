<?php

namespace Drupal\blinker_core\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Style plugin to render each item as a card.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "ui_pattern",
 *   title = @Translation("UI pattern"),
 *   help = @Translation("Displays view content using a UI pattern."),
 *   theme = "views_view_ui_pattern",
 *   display_types = {"normal"}
 * )
 */
class UIPattern extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowClass = FALSE;

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['columns'] = ['default' => 1];
    $options['pattern'] = ['default' => ''];
    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Prepare a list of patterns.
    /** @var \Drupal\ui_patterns\UiPatternsManager $pattern_manager */
    $pattern_manager = \Drupal::service('plugin.manager.ui_patterns');
    $pattern_options = $pattern_manager->getPatternsOptions();

    // TODO: Use patterns with "views row style" tags to populate this list.
    $form['pattern'] = [
      '#type' => 'select',
      '#title' => $this->t('Pattern'),
      '#options' => [
        'card_collection' => $pattern_options['card_collection'],
      ],
      '#default_value' => $this->options['pattern'],
      '#required' => TRUE,
    ];

    // TODO: Use variants to populate this field.
    $form['variant'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of columns'),
      '#attributes' => [
        'min' => 0,
        'max' => 6,
        'step' => 1,
      ],
      '#default_value' => $this->options['variant'],
      '#required' => TRUE,
    ];
  }

}
