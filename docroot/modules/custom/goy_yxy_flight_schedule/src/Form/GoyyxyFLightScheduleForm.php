<?php

namespace Drupal\goy_yxy_flight_schedule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OtpForm.
 */
class GoyyxyFLightScheduleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'goy_yxy_flight_schedule';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#cache'] = ['max-age' => 0];

    $form['markup'] = [
      '#markup' => '<dl class="admin-list"><dt><a href="/admin/config/goy_yxy_flight_schedule/settings">Path Settings</a></dt><dd>Configure YXY Flight Schedule URL or file paths.</dd></dl>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
