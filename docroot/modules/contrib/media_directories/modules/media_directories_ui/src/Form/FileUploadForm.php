<?php

namespace Drupal\media_directories_ui\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form to upload files.
 */
class FileUploadForm extends AddMediaFormBase implements TrustedCallbackInterface {

  /**
   * The element info discovery service.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderUploadElement'];
  }

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
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, Token $token, ThemeManagerInterface $theme_manager, ElementInfoManagerInterface $element_info, RendererInterface $renderer) {
    parent::__construct($entity_type_manager, $current_user, $token, $theme_manager, $renderer);
    $this->elementInfo = $element_info;
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
      $container->get('renderer')
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    $media_type = $this->getMediaType($form_state);
    $source_field = $media_type->getSource()->getConfiguration()['source_field'];
    $field_config = $this->entityTypeManager->getStorage('field_config')->load('media.' . $media_type->id() . '.' . $source_field);

    $process = (array) $this->elementInfo->getInfoProperty('managed_file', '#process', []);
    $pre_render = (array) $this->elementInfo->getInfoProperty('managed_file', '#pre_render', []);

    $form['container']['upload'] = [
      '#type' => 'managed_file',
      '#title' => $field_config->label(),
      '#description' => $this->t('Allowed file extensions: @extensions', ['@extensions' => $field_config->getSetting('file_extensions')]),
      '#upload_validators' => $this->getUploadValidators($media_type),
      '#multiple' => TRUE,
      '#upload_location' => $this->getUploadLocation($field_config->getSettings()),
      '#process' => array_merge(['::validateUploadElement'], $process, ['::processUploadElement']),
      '#pre_render' => array_merge($pre_render, [[static::class, 'preRenderUploadElement']]),
    ];

    return $form;
  }

  /**
   * Validates the upload element.
   *
   * @param array $element
   *   The upload element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The processed upload element.
   */
  public function validateUploadElement(array $element, FormStateInterface $form_state) {
    /*    if ($form_state::hasAnyErrors()) {
    // When an error occurs during uploading files, remove all files so the
    // user can re-upload the files.
    $element['#value'] = [];
    }
    $values = $form_state->getValue('upload', []);
    if (count($values['fids']) > $element['#cardinality'] && $element['#cardinality'] !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
    $form_state->setError($element, $this->t('A maximum of @count files can be uploaded.', [
    '@count' => $element['#cardinality'],
    ]));
    $form_state->setValue('upload', []);
    $element['#value'] = [];
    }*/
    return $element;
  }

  /**
   * Processes an upload (managed_file) element.
   *
   * @param array $element
   *   The upload element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The processed upload element.
   */
  public function processUploadElement(array $element, FormStateInterface $form_state) {
    $element['upload_button']['#submit'] = ['::uploadButtonSubmit'];
    /** @var \Drupal\media\MediaTypeInterface|string $media_type */
    $media_type = $form_state->get('media_type');
    // Limit the validation errors to make sure
    // FormValidator::handleErrorsWithLimitedValidation doesn't remove the
    // current selection from the form state.
    // @see Drupal\Core\Form\FormValidator::handleErrorsWithLimitedValidation()
    $element['upload_button']['#limit_validation_errors'] = [
      ['upload'],
      ['current_selection'],
    ];
    $element['upload_button']['#ajax'] = [
      'callback' => '::updateFormCallback',
      'wrapper' => 'media-library-wrapper',
      // Add a fixed URL to post the form since AJAX forms are automatically
      // posted to <current> instead of $form['#action'].
      // @todo Remove when https://www.drupal.org/project/drupal/issues/2504115
      //   is fixed.
      'url' => Url::fromRoute('media_directories_ui.media.add'),
      'options' => [
        'query' => [
          'media_type' => is_object($media_type) ? $media_type->id() : $media_type,
          'target_bundles' => $this->getTargetBundles($form_state),
          'active_directory' => $this->getDirectory($form_state),
          'cardinality' => $this->getCardinality($form_state),
          'selection_mode' => $this->getSelectionMode($form_state),
          FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
        ],
      ],
    ];

    // If a remove button is present, also allow to submit the from using a new button,
    // as the upload_button will be hidden with .hide-js by core.
    if (isset($element['remove_button'])) {
      $element['proceed_button'] = [
        '#type' => 'submit',
        '#value' => $this->t('Proceed with all files from the list'),
      ];
      if (isset($element['upload_button']['#validate'])) {
        $element['proceed_button']['#validate'] = $element['upload_button']['#validate'];
      }
      if (isset($element['upload_button']['#submit'])) {
        $element['proceed_button']['#submit'] = $element['upload_button']['#submit'];
      }
      if (isset($element['upload_button']['#limit_validation_errors'])) {
        $element['proceed_button']['#limit_validation_errors'] = $element['upload_button']['#limit_validation_errors'];
      }
      if (isset($element['upload_button']['#ajax'])) {
        $element['proceed_button']['#ajax'] = $element['upload_button']['#ajax'];
      }
      if (isset($element['upload_button']['#weight'])) {
        $element['proceed_button']['#weight'] = $element['upload_button']['#weight'];
      }
    }

    return $element;
  }

  /**
   * Render API callback: Hides display of the proceed control.
   *
   * @see \Drupal\file\Element\ManagedFile::preRenderManagedFile()
   */
  public static function preRenderUploadElement($element) {
    if (isset($element['proceed_button'])) {
      // Make sure to be hidden, when the the remove button is hidden.
      if (isset($element['remove_button']['#access'])) {
        $element['proceed_button']['#access'] = $element['remove_button']['#access'];
      }
    }

    return $element;
  }

  /**
   * Submit handler for the upload button, inside the managed_file element.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function uploadButtonSubmit(array $form, FormStateInterface $form_state) {
    $files = $this->entityTypeManager
      ->getStorage('file')
      ->loadMultiple($form_state->getValue('upload', []));
    $this->processInputValues($files, $form, $form_state);
  }

  /**
   * Makes the file of a media permanent.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function prepareMediaEntityForSave(MediaInterface $media) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->get($this->getSourceFieldName($media->bundle->entity))->entity;
    $file->setPermanent();
    $file->save();
  }

}
