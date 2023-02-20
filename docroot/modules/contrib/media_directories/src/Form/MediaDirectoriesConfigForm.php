<?php

namespace Drupal\media_directories\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Media directories configuration form.
 */
class MediaDirectoriesConfigForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'media_directories.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_directories_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('media_directories.settings');

    $vocabularies = $this->entityTypeManager
      ->getStorage('taxonomy_vocabulary')
      ->loadMultiple();

    $options = [];

    $options[''] = $this->t('- None -');

    foreach ($vocabularies as $vocabulary) {
      $options[$vocabulary->id()] = $vocabulary->label();
    }

    $vocabulary_add_url = Url::fromRoute('entity.taxonomy_vocabulary.add_form')->toString();
    $form['directory_taxonomy'] = [
      '#type' => 'select',
      '#title' => $this->t('Taxonomy'),
      '#description' => $this->t('<strong>When changing this setting, all caches will be cleared.</strong>' .
        '<br/>Select an existing vocabulary representing the directory structure or  <a href=":create_vocabulary_url">create a new one</a>.', [
        ':create_vocabulary_url' => $vocabulary_add_url,
      ]),
      '#options' => $options,
      '#default_value' => $config->get('directory_taxonomy'),
    ];

    $form['all_files_in_root'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show all files in Root directory'),
      '#description' => $this->t('By checking this option, Root directory will show all the files.'),
      '#default_value' => $config->get('all_files_in_root'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_vocabulary = $this->config('media_directories.settings')->get('directory_taxonomy');
    $new_vocabulary = $form_state->getValue('directory_taxonomy');
    $this->config('media_directories.settings')
      ->set('directory_taxonomy', $new_vocabulary)
      ->set('all_files_in_root', $form_state->getValue('all_files_in_root'))
      ->save();

    // Clear cache to change base field settings.
    // @see media_directories_entity_base_field_info().
    if ($current_vocabulary !== $new_vocabulary) {
      drupal_flush_all_caches();
      $this->messenger()->addStatus($this->t('Caches cleared.'));
    }

    parent::submitForm($form, $form_state);
  }

}
