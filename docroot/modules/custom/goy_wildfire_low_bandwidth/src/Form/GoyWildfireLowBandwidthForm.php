<?php

namespace Drupal\goy_wildfire_low_bandwidth\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OtpForm.
 */
class GoyWildfireLowBandwidthForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'low_bandwidth_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#cache'] = ['max-age' => 0];

    $form['markup'] = [
      '#markup' => '<dl class="admin-list"><dt><a href="/admin/config/goy_wildfire_low_bandwidth/settings">Settings</a></dt><dd>Configure Wildfire Low Bandwidth URL and cache settings.</dd></dl>',
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
