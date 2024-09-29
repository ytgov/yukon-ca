<?php

namespace Drupal\yukon_w3_custom\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters nodes by year of publish date.
 *
 * @ViewsFilter("event_year_filter")
 */
class YearEventFIlter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return $this->t('Filters nodes by year of event start date.');
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $current_year = date('Y');
    $future = date('Y', strtotime('+3 year'));
    $options = [];

    for ($year = $current_year; $year <= $future; $year++) {
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
    // dump($query); die;.
    if (!empty($this->value[0])) {
      $query->addWhereExpression(0, "EXTRACT(YEAR FROM node__field_event_start_time.field_event_start_time_value) = :year", [':year' => $this->value[0]]);
    }
  }

}
