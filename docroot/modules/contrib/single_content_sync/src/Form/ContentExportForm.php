<?php

namespace Drupal\single_content_sync\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\node\Plugin\views\filter\Access;
use Drupal\single_content_sync\ContentExporterInterface;
use Drupal\single_content_sync\ContentFileGeneratorInterface;
use Drupal\single_content_sync\ContentSyncHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to export content.
 *
 * @package Drupal\single_content_sync\Form
 */
class ContentExportForm extends FormBase {

  /**
   * The content exporter service.
   *
   * @var \Drupal\single_content_sync\ContentExporterInterface
   */
  protected $contentExporter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The content file generator.
   *
   * @var \Drupal\single_content_sync\ContentFileGeneratorInterface
   */
  protected $fileGenerator;

  /**
   * The content sync helper.
   *
   * @var \Drupal\single_content_sync\ContentSyncHelperInterface
   */
  protected $contentSyncHelper;

  /**
   * ContentExportForm constructor.
   *
   * @param \Drupal\single_content_sync\ContentExporterInterface $content_exporter
   *   The content exporter service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\single_content_sync\ContentFileGeneratorInterface $file_generator
   *   The content file generator.
   * @param \Drupal\single_content_sync\ContentSyncHelperInterface $content_sync_helper
   *   The content sync helper.
   */
  public function __construct(ContentExporterInterface $content_exporter, EntityTypeManagerInterface $entity_type_manager, ContentFileGeneratorInterface $file_generator, ContentSyncHelperInterface $content_sync_helper) {
    $this->contentExporter = $content_exporter;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileGenerator = $file_generator;
    $this->contentSyncHelper = $content_sync_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('single_content_sync.exporter'),
      $container->get('entity_type.manager'),
      $container->get('single_content_sync.file_generator'),
      $container->get('single_content_sync.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'single_content_sync_export_form';
  }

  /**
   * Download file automatically when it requested.
   *
   * @param array $form
   *   The form array.
   */
  protected function handleAutoFileDownload(array &$form) {
    // Don't check for file downloads if this is a submit request.
    if ($this->getRequest()->getMethod() !== 'POST') {
      if ($filename = $this->getRequest()->query->get('file')) {
        $files = $this->entityTypeManager->getStorage('file')
          ->loadByProperties(['filename' => $filename]);
        /** @var \Drupal\file\FileInterface $file */
        $file = array_pop($files);
        if (file_exists($file->getFileUri())) {
          $download_url = Url::fromRoute('single_content_sync.file_download', [], [
            'query' => ['file' => $filename],
            'absolute' => TRUE,
          ])->toString();

          $form['#attached']['html_head'][] = [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'http-equiv' => 'refresh',
                'content' => '0; url=' . $download_url,
              ],
            ],
            'single_content_sync_export_download',
          ];
          return;
        }

        // If the file does not exist, something went wrong.
        $this->messenger()->addError($this->t('The export file could not be found, please try again.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->handleAutoFileDownload($form);

    $extract_translations = $form_state->getValue('translation', FALSE);
    $parameters = $this->getRouteMatch()->getParameters();
    $entity = $this->contentSyncHelper->getDefaultLanguageEntity($parameters);

    $export_in_yaml = $this->contentExporter->doExportToYml($entity, $extract_translations);

    $form['output'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exported content'),
      '#attributes' => [
        'data-yaml-editor' => 'true',
      ],
      '#wrapper_attributes' => [
        'id' => 'exported-content',
      ],
      '#value' => $export_in_yaml,
      '#attached' => [
        'library' => [
          'single_content_sync/yaml_editor',
        ],
      ],
    ];

    $form['translation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include all translations?'),
      '#description' => $this->t('The exported content will be refreshed to preview it with translations.'),
      '#ajax' => [
        'callback' => '::refreshContent',
        'wrapper' => 'exported-content',
        'effect' => 'fade',
        'progress' => [
          'type' => 'fullscreen',
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['download_zip'] = [
      '#type' => 'submit',
      '#name' => 'download_zip',
      '#button_type' => 'primary',
      '#value' => $this->t('Download as a zip with all assets'),
    ];

    $form['actions']['download_file'] = [
      '#type' => 'submit',
      '#name' => 'download_file',
      '#value' => $this->t('Download as a file'),
    ];

    return $form;
  }

  /**
   * Ajax callback to refresh output field.
   */
  public function refreshContent(array &$form, FormStateInterface $form_state) {
    // Clean up warning messages when refreshing field.
    $this->messenger()->deleteByType('warning');

    return $form['output'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $extract_translations = $form_state->getValue('translation', FALSE);
    $parameters = $this->getRouteMatch()->getParameters();
    $entity = $this->contentSyncHelper->getDefaultLanguageEntity($parameters);

    switch ($button['#name']) {
      case 'download_file':
        $file = $this->fileGenerator->generateYamlFile($entity, $extract_translations);
        break;

      case 'download_zip':
        $file = $this->fileGenerator->generateZipFile($entity, $extract_translations);
        break;
    }

    // Display message to download a file immediately.
    if (isset($file) && $file instanceof FileInterface) {
      $file_name = $file->getFileName();

      $url = Url::fromRoute('single_content_sync.file_download', [], [
        'query' => ['file' => $file_name],
        'absolute' => TRUE,
      ]);
      $message = $this->t('Your download should begin now. If it does not start, download the file @link.', [
        '@link' => Link::fromTextAndUrl($this->t('here'), $url)->toString(),
      ]);
      $this->messenger()->addStatus($message);

      $form_state->setRedirect($this->getRouteMatch()->getRouteName(), $this->getRouteMatch()->getRawParameters()->all(), [
        'query' => [
          'file' => $file_name,
        ],
      ]);
    }
  }

  /**
   * Check if user has access to the export form.
   */
  public function access() {
    $parameters = $this->getRouteMatch()->getParameters();
    $entity = $parameters->getIterator()->current();

    if (is_string($entity)) {
      $entity = $parameters->get($entity);
    }

    if (!$entity instanceof EntityInterface) {
      return AccessResult::forbidden();
    }

    $hasAccess = $this->contentSyncHelper->access($entity);

    return $hasAccess ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
