<?php

namespace Drupal\single_content_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentSyncConfigForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'single_content_sync_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'single_content_sync.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('single_content_sync.settings');
    $entity_types = $this->entityTypeManager->getDefinitions();

    $output = [];
    foreach ($entity_types as $entity_type) {
      if ($entity_type->hasLinkTemplate('single-content:export')) {
        $output[$entity_type->id()] = [
          'entity_type' => $entity_type->getLabel(),
        ];
      }
    }

    $header = [
      'entity_type' => $this->t('Entity type'),
    ];

    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $output,
      '#empty' => $this->t('No available entity types'),
      '#default_value' => $config->get('allowed_entity_types'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $source = $form_state->getValue('table');

    $this->configFactory->getEditable('single_content_sync.settings')
      ->set('allowed_entity_types', array_filter($source))
      ->save();

    parent::submitForm($form, $form_state);

    // Flush cache to update operation forms.
    drupal_flush_all_caches();
  }

}
