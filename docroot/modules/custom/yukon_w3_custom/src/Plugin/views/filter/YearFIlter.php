<?php

namespace Drupal\yukon_w3_custom\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters nodes by year of publish date.
 *
 * Start year defaults to 2022, can be set in settings.php
 *
 *    $settings['yukon_w3_custom_view_filter_year']['start'] = 2023;
 *
 * @ViewsFilter("year_filter")
 */
class YearFIlter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return $this->t('Filters nodes by year of publish date.');
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $settings = \Drupal::service('settings');
    $start_year = intval($settings->get('yukon_w3_custom_view_filter_year', ['start' => 2022])['start']);

    $current_year = date('Y');
    $options = [];

    for ($year = $start_year; $year <= $current_year; $year++) {
      $options[$year] = $year;
    }

    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Year'),
      '#options' => $options,
      '#default_value' => $current_year,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;

    if (!empty($this->value[0])) {
      $query->addWhereExpression(0, "EXTRACT(YEAR FROM FROM_UNIXTIME(node_field_data.created)) = :year", [':year' => $this->value[0]]);
    }
  }

}
