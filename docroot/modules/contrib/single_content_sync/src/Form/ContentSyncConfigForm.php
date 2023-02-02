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
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');

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

    $form['site_uuid_check'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Site UUID check'),
      '#description' => $this->t('Enables checking for source/destination Site UUID value during the export. If imported content has been retrieved from another instance of the site, that does not match UUID value of the current site, it will not be imported.'),
      '#default_value' => $config->get('site_uuid_check'),
    ];

    $entity_types = $this->entityTypeManager->getDefinitions();
    $allowed_types = ['#tree' => TRUE];
    foreach ($entity_types as $entity_type) {
      if (!$entity_type->hasLinkTemplate('single-content:export')) {
        continue;
      }

      $entity_type_id = $entity_type->id();
      $allowed_types[$entity_type_id] = [
        '#type' => 'fieldset',
      ];
      $allowed_types[$entity_type_id]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $entity_type->getLabel(),
        '#default_value' => isset($config->get('allowed_entity_types')[$entity_type_id]),
      ];

      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      if ($bundles) {
        $bundles_as_options = [];
        foreach ($bundles as $bundle_id => $bundle_info) {
          $bundles_as_options[$bundle_id] = $bundle_info['label'] ?? $bundle_id;
        }
        $allowed_types[$entity_type_id]['bundles'] = [
          '#type' => 'checkboxes',
          '#title' => $entity_type->getBundleLabel(),
          '#options' => $bundles_as_options,
          '#default_value' => array_keys($config->get('allowed_entity_types')[$entity_type_id] ?? []),
          '#description_display' => 'before',
          '#description' => $this->t('Leave empty to enable on all @plural_label.', [
            '@plural_label' => $entity_type->getPluralLabel(),
          ]),
          '#states' => [
            'visible' => [
              ':input[name="allowed_types[' . $entity_type_id . '][enabled]"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }
    }
    $form['allowed_types'] = $allowed_types;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $source = $form_state->getValue('allowed_types');
    $allowed_types = [];
    foreach ($source as $entity_type_id => $info) {
      if ($info['enabled']) {
        $allowed_types[$entity_type_id] = array_filter($info['bundles']);
      }
    }

    $this->configFactory->getEditable('single_content_sync.settings')
      ->set('allowed_entity_types', $allowed_types)
      ->set('site_uuid_check', $form_state->getValue('site_uuid_check'))
      ->save();

    parent::submitForm($form, $form_state);

    // Flush cache to update operation forms.
    drupal_flush_all_caches();
  }

}
