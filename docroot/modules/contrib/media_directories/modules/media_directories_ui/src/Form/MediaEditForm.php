<?php

namespace Drupal\media_directories_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_directories_ui\Ajax\LoadDirectoryContent;

/**
 * Class MediaEditForm.
 *
 * Uses code and logic from core. We could try to integrate core directly,
 * but it might be too unstable in this stage.
 *
 * @package Drupal\media_directories_ui\Form
 */
class MediaEditForm extends AddMediaFormBase {

  /**
   * Temporary store of the media entity.
   *
   * @var \Drupal\media\MediaInterface
   */
  public $media;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_directories_media_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMediaType(FormStateInterface $form_state) {
    if (isset($this->media)) {
      return $this->entityTypeManager->getStorage('media_type')->load($this->media->bundle());
    }

    return parent::getMediaType($form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetBundles(FormStateInterface $form_state) {
    $medias = $form_state->get('media');
    if (is_array($medias)) {
      $bundles = [];
      foreach ($medias as $media) {
        $bundles[] = $media->bundle();
      }
      return $bundles;
    }

    return parent::getTargetBundles($form_state);;
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Add CSS for the left sided preview.
    $form['#attached']['library'][] = 'media_directories_ui/media-library.quick-edit-dialog';

    // Hide the creation message.
    $form['media']['description']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildInputElement(array $form, FormStateInterface $form_state) {
    // No need for an input element in the edit form.
  }

  /**
   * Builds the sub-form for setting required fields on a new media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A new, unsaved media item.
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param int $delta
   *   The delta of the media item.
   *
   * @return array
   *   The element containing the required fields sub-form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function buildEntityFormElement(MediaInterface $media, array $form, FormStateInterface $form_state, $delta) {
    // Set the media object to be used in overwritten methods.
    $this->media = $media;

    $element = parent::buildEntityFormElement($media, $form, $form_state, $delta);

    // Make the language field un-editable.
    if (isset($element['fields']['langcode'])) {
      $languages = $media->getTranslationLanguages();
      $translations = [];
      foreach ($languages as $langcode => $language) {
        $translations[$langcode] = $media->id();
      }
      // We allow altering the (source-)language, if no translation is made yet.
      if (count($translations) > 1) {
        $element['fields']['langcode']['#disabled'] = TRUE;
        $element['fields']['langcode']['#suffix'] = '<div>' . $this->t('In this dialog you are only allowed to change the source language of medias having no translation yet.') . '</div>';
      }
    }

    // We show the left sided preview and provide a link to the full media edit page.
    if (!empty($element['preview'])) {
      $element['preview']['#access'] = TRUE;
      $link = new Link($this->t('Edit in new tab'), $media->toUrl('edit-form'));
      $element['preview']['edit_link'] = $link->toRenderable();
      $element['preview']['edit_link']['#attributes']['class'][] = 'edit-link';
      $element['preview']['edit_link']['#attributes']['target'] = '_blank';
    }

    // Remove original button added by ManagedFile::processManagedFile().
    if (!empty($element['remove_button'])) {
      $element['remove_button']['#access'] = FALSE;
    }

    return $element;
  }

  /**
   * Returns the name of the source field for a media type.
   *
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   The media type to get the source field name for.
   *
   * @return string
   *   The name of the media type's source field.
   */
  protected function getSourceFieldName(MediaTypeInterface $media_type) {
    return $media_type->getSource()
      ->getSourceFieldDefinition($media_type)
      ->getName();
  }

  /**
   * Returns an array of supported actions for the form.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   An actions element containing the actions of the form.
   */
  protected function buildActions(array $form, FormStateInterface $form_state) {
    return [
      '#type' => 'actions',
      'save' => [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Save'),
        '#ajax' => [
          'callback' => [get_called_class() , 'saveMedia'],
          'wrapper' => 'media-library-add-form-wrapper',
        ],
      ],
    ];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public static function saveMedia(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $added_media = $form_state->get('media');

    foreach ($added_media as $delta => $media) {
      EntityFormDisplay::collectRenderDisplay($media, 'media_library')
        ->extractFormValues($media, $form['media'][$delta]['fields'], $form_state);
      // $this->prepareMediaEntityForSave($media);
      $media->save();
    }

    $response->addCommand(new LoadDirectoryContent());
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here.
  }

}
