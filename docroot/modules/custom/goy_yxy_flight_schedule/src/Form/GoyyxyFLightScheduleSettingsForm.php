<?php

namespace Drupal\goy_yxy_flight_schedule\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures forms module settings.
 */
class GoyyxyFLightScheduleSettingsForm extends ConfigFormBase {

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
    return 'goy_yxy_flight_schedule_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'goy_yxy_flight_schedule.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('goy_yxy_flight_schedule.settings');

    $form['goy_yxy_flight_schedule_xml_source_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('XML Source URL or File Path'),
      '#default_value' => $config->get('goy_yxy_flight_schedule_xml_source_path'),
      '#description' => $this->t('URL or file path to the Terminal Systems-supplied arrivals and departures XML files'),
      '#required' => TRUE,
    ];

    $form['goy_yxy_flight_schedule_arrivals_file_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Arrivals XML File Name'),
      '#default_value' => $config->get('goy_yxy_flight_schedule_arrivals_file_name'),
      '#description' => $this->t('Arrivals XML file name.'),
      '#required' => TRUE,
    ];

    $form['goy_yxy_flight_schedule_departures_file_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Departures XML File Name'),
      '#default_value' => $config->get('goy_yxy_flight_schedule_departures_file_name'),
      '#description' => $this->t('Departures XML file name.'),
      '#required' => TRUE,
    ];

    $form['goy_yxy_flight_schedule_airline_icon_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Airline Icon Images URL'),
      '#default_value' => $config->get('goy_yxy_flight_schedule_airline_icon_path'),
      '#description' => $this->t('Base URL of the airline icon image files'),
      '#required' => TRUE,
    ];

    $form['goy_yxy_flight_schedule_weather_icon_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Weather Conditions Icon Images URL'),
      '#default_value' => $config->get('goy_yxy_flight_schedule_weather_icon_path'),
      '#description' => $this->t('Base URL of the weather conditions icon image files'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('goy_yxy_flight_schedule.settings')
      ->set('goy_yxy_flight_schedule_xml_source_path', $form_state->getValue('goy_yxy_flight_schedule_xml_source_path'))
      ->set('goy_yxy_flight_schedule_arrivals_file_name', $form_state->getValue('goy_yxy_flight_schedule_arrivals_file_name'))
      ->set('goy_yxy_flight_schedule_departures_file_name', $form_state->getValue('goy_yxy_flight_schedule_departures_file_name'))
      ->set('goy_yxy_flight_schedule_airline_icon_path', $form_state->getValue('goy_yxy_flight_schedule_airline_icon_path'))
      ->set('goy_yxy_flight_schedule_weather_icon_path', $form_state->getValue('goy_yxy_flight_schedule_weather_icon_path'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
