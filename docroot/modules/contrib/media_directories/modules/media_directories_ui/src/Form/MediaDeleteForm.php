<?php

namespace Drupal\media_directories_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_directories_ui\Ajax\LoadDirectoryContent;

/**
 * A form to delete medias.
 */
class MediaDeleteForm extends ConfirmFormBase {

  /**
   * The medias to delete.
   *
   * @var \Drupal\media\Entity\Media[]
   */
  protected $entities;

  /**
   * The form context.
   *
   * @var array
   */
  protected $formContext;

  /**
   * {@inheritDoc}
   */
  public function getQuestion() {
    if (count($this->entities) > 1) {
      return $this->t('Do you want to delete @count items?', ['@count' => count($this->entities)]);
    }

    $first_entity = reset($this->entities);

    return $this->t('Do you want to delete @name?', ['@name' => $first_entity->getName()]);
  }

  /**
   * {@inheritDoc}
   */
  public function getCancelUrl() {
    // TODO: Implement getCancelUrl() method.
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'media_delete_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();

    if (isset($build_info['args'][0])) {
      $this->formContext = $build_info['args'][0];
      $this->entities = $this->formContext['media_items'];

      $form['media_items'] = [
        '#tree' => TRUE,
      ];

      foreach ($this->entities as $entity) {

        $form['media_items'][] = [
          '#type' => 'hidden',
          '#value' => $entity->id(),
        ];
      }
    }

    $form['question']['#markup'] = '<h4>' . $this->getQuestion() . '</h4>';

    $form = parent::buildForm($form, $form_state);

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $form['actions']['submit']['#ajax'] = [
      'callback' => [$this, 'submitModalAjax'],
      'event' => 'click',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#button_type' => 'secondary',
      '#ajax' => [
        'callback' => [$this, 'closeModalAjax'],
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback when the form is submitted.
   */
  public function submitModalAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    foreach ($this->entities as $entity) {
      $entity->delete();
    }

    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new LoadDirectoryContent());

    return $response;
  }

  /**
   * Close modal dialog.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function closeModalAjax() {
    $response = new AjaxResponse();
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
    // TODO: Implement submitForm() method.
  }

}
