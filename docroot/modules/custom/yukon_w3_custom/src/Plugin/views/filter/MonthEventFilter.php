<?php

namespace Drupal\yukon_w3_custom\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters nodes by event month of publish date.
 *
 * @ViewsFilter("event_month_filter")
 */
class MonthEventFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return $this->t('Filters nodes by month of event start date.');
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#options' => [
        'january' => $this->t('Jan'),
        'february' => $this->t('Feb'),
        'march' => $this->t('Mar'),
        'april' => $this->t('Apr'),
        'may' => $this->t('May'),
        'june' => $this->t('Jun'),
        'july' => $this->t('Jul'),
        'august' => $this->t('Aug'),
        'september' => $this->t('Sep'),
        'october' => $this->t('Oct'),
        'november' => $this->t('Nov'),
        'december' => $this->t('Dec'),
      ],
      '#default_value' => $this->value,
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
      $month_num = date('m', strtotime($this->value[0]));
      $query->addWhereExpression(0, "EXTRACT(MONTH FROM node__field_event_start_time.field_event_start_time_value) = :month", [':month' => $month_num]);
    }
  }

}
