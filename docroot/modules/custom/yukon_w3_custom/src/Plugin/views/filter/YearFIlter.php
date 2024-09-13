<?php

namespace Drupal\yukon_w3_custom\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters nodes by year of publish date.
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
    $current_year = date('Y');
    $options = [];

    for ($year = 2018; $year <= $current_year; $year++) {
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
