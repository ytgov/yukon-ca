<?php

namespace Drupal\single_content_sync\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\single_content_sync\ContentImporterInterface;
use Drupal\single_content_sync\ContentSyncHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to import a content.
 *
 * @package Drupal\single_content_sync\Form
 */
class ContentImportForm extends FormBase {

  /**
   * The content importer service.
   *
   * @var \Drupal\single_content_sync\ContentImporterInterface
   */
  protected $contentImporter;

  /**
   * The content sync helper.
   *
   * @var \Drupal\single_content_sync\ContentSyncHelperInterface
   */
  protected $contentSyncHelper;

  /**
   * ContentImportForm constructor.
   *
   * @param \Drupal\single_content_sync\ContentImporterInterface $content_importer
   *   The content importer service.
   * @param \Drupal\single_content_sync\ContentSyncHelperInterface $content_sync_helper
   *   The content sync helper.
   */
  public function __construct(ContentImporterInterface $content_importer, ContentSyncHelperInterface $content_sync_helper) {
    $this->contentImporter = $content_importer;
    $this->contentSyncHelper = $content_sync_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('single_content_sync.importer'),
      $container->get('single_content_sync.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'single_content_sync_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default_scheme = $this->config('system.file')->get('default_scheme');

    $form['upload_fid'] = [
      '#type' => 'managed_file',
      '#upload_loction' => "{$default_scheme}://import/zip",
      '#upload_validators' => [
        'file_validate_extensions' => ['zip yml'],
      ],
      '#title' => $this->t('Upload a file with content to import'),
      '#description' => $this->t('Upload a Zip or YAML file with the previously exported content.'),
    ];
    $form['content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paste the content from the clipboard'),
      '#required' => FALSE,
      '#prefix' => '<br><p>' . $this->t('OR') . '</p><br>',
      '#attributes' => [
        'data-yaml-editor' => 'true',
      ],
      '#attached' => [
        'library' => [
          'single_content_sync/yaml_editor',
        ],
      ],
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $upload_file = $form_state->getValue('upload_fid');
    $content = $form_state->getValue('content');

    if (!$upload_file && !$content) {
      $form_state->setErrorByName('upload_fid' & 'content', $this->t('Please fill in one of the fields to import your content.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle uploaded file first.
    if ($upload_file = $form_state->getValue('upload_fid')) {
      $fid = reset($upload_file);
      $file_real_path = $this->contentSyncHelper->getFileRealPathById($fid);
      $file_info = pathinfo($file_real_path);
      $entity = NULL;

      try {
        if ($file_info['extension'] === 'zip') {
          $this->contentImporter->importFromZip($file_real_path);
        }
        else {
          $entity = $this->contentImporter->importFromFile($file_real_path);
        }
      }
      catch (\Exception $e) {
        $this->messenger()->addError($e->getMessage());
        return;
      }
    }
    else {
      try {
        $content_array = $this->contentSyncHelper->validateYamlFileContent($form_state->getValue('content'));
        $entity = $this->contentImporter->doImport($content_array);
      }
      catch (\Exception $e) {
        $this->messenger()->addError($e->getMessage());
        return;
      }
    }

    if ($entity) {
      $this->messenger()->addStatus($this->t('The content has been synced @link', [
        '@link' => $entity->toLink()->toString(),
      ]));
    }
  }

}
