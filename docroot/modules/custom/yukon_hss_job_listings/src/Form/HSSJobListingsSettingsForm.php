<?php

declare(strict_types=1);

namespace Drupal\yukon_hss_job_listings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure HSS Job Listings settings for this site.
 */
final class HSSJobListingsSettingsForm extends ConfigFormBase {

  const SETTINGS_KEY = 'yukon_hss_job_listings.settings';
  const HRSMART_PARAGRAPHS_FIELD_NAME = 'field_paragraphs';
  const HRSMART_PARAGRAPH_BUNDLE_NAME = 'hrsmart_job_listings';
  const HRSMART_DATA_FIELD_NAME = 'field_hrsmart_job_listings_data';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'yukon_hss_job_listings_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::SETTINGS_KEY];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['hrsmart_xml_api_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('HRSmart XML API endpoint URI'),
      '#default_value' => $this->config(self::SETTINGS_KEY)->get('hrsmart_xml_api_uri'),
      '#description' => $this->t('API endpoint URI for the HRSmart/Deltek jobs listings in XML format. Do not include the API key portion of the URI here.'),
      '#required' => TRUE,
    ];

    $form['hrsmart_xml_api_key'] = [
      '#type' => 'item',
      '#title' => $this->t('HRSmart XML API authentication key'),
      '#description' => $this->t("Set the authentication key for the HRSmart/Deltek jobs listings API in the Drupal settings file: \$config['yukon_hss_job_listings.settings']['hrsmart_xml_api_key'] = 'xxx...';"),
    ];

    $form['hrsmart_content_node_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Node ID of the content item displaying the HRSmart job listings'),
      '#default_value' => $this->config(self::SETTINGS_KEY)->get('hrsmart_content_node_id'),
      '#description' => $this->t('Edit the content item to view the node ID number in the URL path.'),
      '#required' => TRUE,
    ];

    $form['hrsmart_paragraphs_field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine name of the paragraphs field'),
      '#default_value' => $this->config(self::SETTINGS_KEY)->get('hrsmart_paragraphs_field_name') ?? self::HRSMART_PARAGRAPHS_FIELD_NAME,
      '#description' => $this->t("Look up the machine name for this field in the content type's Manage Fields tab."),
      '#required' => TRUE,
    ];

    $form['hrsmart_paragraph_bundle_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine name of the HRSmart paragraph type'),
      '#default_value' => $this->config(self::SETTINGS_KEY)->get('hrsmart_paragraph_bundle_name') ?? self::HRSMART_PARAGRAPH_BUNDLE_NAME,
      '#description' => $this->t('Look up the machine name for the "HRSmart job listings" paragraph type.'),
      '#required' => TRUE,
    ];

    $form['hrsmart_data_field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine name of the HRSmart job listings field type'),
      '#default_value' => $this->config(self::SETTINGS_KEY)->get('hrsmart_data_field_name') ?? self::HRSMART_DATA_FIELD_NAME,
      '#description' => $this->t('Look up the machine name for the "HSS Jobs Listings data" field type within the above paragraph type.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(self::SETTINGS_KEY)
      ->set('hrsmart_xml_api_uri', $form_state->getValue('hrsmart_xml_api_uri'))
      ->set('hrsmart_content_node_id', $form_state->getValue('hrsmart_content_node_id'))
      ->set('hrsmart_paragraphs_field_name', $form_state->getValue('hrsmart_paragraphs_field_name'))
      ->set('hrsmart_paragraph_bundle_name', $form_state->getValue('hrsmart_paragraph_bundle_name'))
      ->set('hrsmart_data_field_name', $form_state->getValue('hrsmart_data_field_name'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
