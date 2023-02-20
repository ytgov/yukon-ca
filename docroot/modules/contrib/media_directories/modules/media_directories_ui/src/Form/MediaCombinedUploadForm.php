<?php

namespace Drupal\media_directories_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\media\Entity\MediaType;
use Drupal\media_directories_ui\MediaDirectoriesUiHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form to upload media of different bundles.
 */
class MediaCombinedUploadForm extends FileUploadForm {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Our helper service.
   *
   * @var \Drupal\media_directories_ui\MediaDirectoriesUiHelper
   */
  protected $mediaDirectoriesUiHelper;

  /**
   * The file repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * AddMediaFormBase constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\media_directories_ui\MediaDirectoriesUiHelper $media_directories_ui_helper
   *   The media directories ui helper.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, Token $token, ThemeManagerInterface $theme_manager, ElementInfoManagerInterface $element_info, RendererInterface $renderer, FileSystemInterface $file_system, MediaDirectoriesUiHelper $media_directories_ui_helper, FileRepositoryInterface $file_repository) {
    parent::__construct($entity_type_manager, $current_user, $token, $theme_manager, $element_info, $renderer);
    $this->fileSystem = $file_system;
    $this->mediaDirectoriesUiHelper = $media_directories_ui_helper;
    $this->fileRepository = $file_repository;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('token'),
      $container->get('theme.manager'),
      $container->get('element_info'),
      $container->get('renderer'),
      $container->get('file_system'),
      $container->get('media_directories_ui.helper'),
      $container->get('file.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_directories_combined_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildInputElement(array $form, FormStateInterface $form_state) {
    $target_types = $this->getTargetBundles($form_state);
    $validators_by_media_type = [];
    foreach ($target_types as $type) {
      $validators_by_media_type[$type] = $this->getUploadValidators(MediaType::load($type));
    }

    $pre_render = (array) $this->elementInfo->getInfoProperty('managed_file', '#pre_render', []);

    $form['container']['upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Select files'),
      '#description' => $this->t('Allowed file extensions: @extensions', ['@extensions' => $this->mediaDirectoriesUiHelper->getValidExtensions($target_types)]),
      '#multiple' => TRUE,
      // Upload to temporary folder. Needs to be moved into correct folder after saving.
      '#upload_location' => 'temporary://',
      '#upload_validators' => [
        'media_directories_ui_file_validator' => [$validators_by_media_type],
        // We need to respect core's _file_save_upload_single, by giving all extensions again.
        'file_validate_extensions' => [$this->mediaDirectoriesUiHelper->getValidExtensions($target_types)],
      ],
      '#process' => [
        ['Drupal\file\Element\ManagedFile', 'processManagedFile'],
        '::processUploadElement',
      ],
      '#pre_render' => array_merge($pre_render, [[static::class, 'preRenderUploadElement']]),
    ];

    return $form;
  }

  /**
   * Creates media items from source field input values.
   *
   * @param mixed[] $source_field_values
   *   The values for source fields of the media items.
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function processInputValues(array $source_field_values, array $form, FormStateInterface $form_state) {
    $media = [];

    foreach ($source_field_values as $source_field_value) {
      $media_type = $this->mediaDirectoriesUiHelper->getMediaType($source_field_value);
      $media_storage = $this->entityTypeManager->getStorage('media');
      $source_field_name = $this->getSourceFieldName($media_type);

      $field_config = $this->entityTypeManager->getStorage('field_config')->load('media.' . $media_type->id() . '.' . $source_field_name);
      $destination = $this->getUploadLocation($field_config->getSettings());
      if ($this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
        $source_field_value = $this->fileRepository->move($source_field_value, $destination);
      }

      $media[] = $this->createMediaFromValue($media_type, $media_storage, $source_field_name, $source_field_value, $form_state);
    }

    // Re-key the media items before setting them in the form state.
    $form_state->set('media', array_values($media));
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function updateFormCallback(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $wrapper_id = $triggering_element['#ajax']['wrapper'];
    $added_media = $form_state->get('media');

    $response = new AjaxResponse();

    // When the source field input contains errors, replace the existing form to
    // let the user change the source field input. If the user input is valid,
    // the entire modal is replaced with the second step of the form to show the
    // form fields for each media item.
    if ($form_state::hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#media-library-add-form-wrapper', $form));
      return $response;
    }

    // Check if the remove button is clicked.
    if (end($triggering_element['#parents']) === 'remove_button') {
      // When the list of added media is empty, return to the media library and
      // shift focus back to the first tabbable element (which should be the
      // source field).
      if (empty($added_media)) {
        $build = [
          '#theme' => 'media_directories_add',
          '#selected_type' => 'combined_upload',
          '#active_directory' => -1,
          '#target_bundles' => $this->getTargetBundles($form_state),
          '#media_library_form_rebuild' => TRUE,
        ];
        $form_state->setRebuild();
        $response->addCommand(new ReplaceCommand('#media-library-add-form-wrapper', $build));
      }
      // When there are still more items, update the form and shift the focus to
      // the next media item. If the last list item is removed, shift focus to
      // the previous item.
      else {
        $response->addCommand(new ReplaceCommand("#$wrapper_id", $form));
      }
    }
    // Update the form and shift focus to the added media items.
    else {
      $response->addCommand(new ReplaceCommand("#$wrapper_id", $form));
    }

    return $response;
  }

}
