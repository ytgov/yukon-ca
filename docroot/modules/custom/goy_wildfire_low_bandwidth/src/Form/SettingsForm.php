<?php

namespace Drupal\goy_wildfire_low_bandwidth\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures forms module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new SiteConfiguration Form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    parent::__construct($config_factory);

    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_mail_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'low_seetings.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('low_seetings.settings');

    $form['goy_wildfire_low_bandwidth_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Goy Wildfire Low Bandwidth Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['goy_wildfire_low_bandwidth_settings']['goy_wildfire_low_bandwidth_districts_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('District URL'),
      '#default_value' => $config->get('goy_wildfire_low_bandwidth_districts_url'),
      '#description' => $this->t('The URL for the JSON file containing the list of districts.'),
      '#required' => TRUE,
    ];
    $form['goy_wildfire_low_bandwidth_settings']['goy_wildfire_low_bandwidth_wildfire_status_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wildfire Status URL'),
      '#default_value' => $config->get('goy_wildfire_low_bandwidth_wildfire_status_url'),
      '#description' => $this->t('The URL for the JSON file containing detailed wildfire status.'),
      '#required' => TRUE,
    ];
    $form['goy_wildfire_low_bandwidth_settings']['goy_wildfire_low_bandwidth_where_clause'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Where clause'),
      '#default_value' => $config->get('goy_wildfire_low_bandwidth_where_clause'),
      '#description' => $this->t('The WHERE clause for the SQL query. This is appended to both the urls above.'),
      '#required' => TRUE,
    ];
    $form['goy_wildfire_low_bandwidth_settings']['goy_wildfire_low_bandwidth_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#default_value' => $config->get('goy_wildfire_low_bandwidth_suffix'),
      '#description' => $this->t('The suffix for the JSON URL. e.g. &f=pjson&returnGeometry=false&outFields=*'),
      '#required' => TRUE,
    ];
    $form['goy_wildfire_low_bandwidth_settings']['goy_wildfire_low_bandwidth_cache_lifetime_seconds'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache lifetime in seconds'),
      '#default_value' => $config->get('goy_wildfire_low_bandwidth_cache_lifetime_seconds'),
      '#description' => $this->t('The length of time, in seconds, that the Wildfire Information is cached before being re-generated.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('low_seetings.settings')
      ->set('goy_wildfire_low_bandwidth_districts_url', $form_state->getValue('goy_wildfire_low_bandwidth_districts_url'))
      ->set('goy_wildfire_low_bandwidth_wildfire_status_url', $form_state->getValue('goy_wildfire_low_bandwidth_wildfire_status_url'))
      ->set('goy_wildfire_low_bandwidth_where_clause', $form_state->getValue('goy_wildfire_low_bandwidth_where_clause'))
      ->set('goy_wildfire_low_bandwidth_suffix', $form_state->getValue('goy_wildfire_low_bandwidth_suffix'))
      ->set('goy_wildfire_low_bandwidth_cache_lifetime_seconds', $form_state->getValue('goy_wildfire_low_bandwidth_cache_lifetime_seconds'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
