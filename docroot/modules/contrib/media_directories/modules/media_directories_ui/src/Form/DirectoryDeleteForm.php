<?php

namespace Drupal\media_directories_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_directories_ui\Ajax\RefreshDirectoryTree;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to delete a directory.
 */
class DirectoryDeleteForm extends ConfirmFormBase {

  /**
   * The direcory to delete.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $directory;

  /**
   * Form context.
   *
   * @var array
   */
  protected $formContext;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DirectoryDeleteForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete @name?', ['@name' => $this->directory->getName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // TODO: Implement getCancelUrl() method.
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'directory_delete_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();

    if (isset($build_info['args'][0])) {
      $this->formContext = $build_info['args'][0];
      $this->directory = $this->formContext['directory'];

      $form['directory_id'] = [
        '#type' => 'hidden',
        '#value' => $this->directory->id(),
      ];
    }

    if ($this->directory->access('delete')) {
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
    }
    else {
      $form['permission_info']['#markup'] = $this->t('No permission found to delete this directory.');
    }

    return $form;
  }

  /**
   * Ajax callback when the form is submitted.
   */
  public function submitModalAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($this->directory->access('delete')) {
      $query = $this->entityTypeManager->getStorage('media')->getQuery();
      $query->condition('directory', $this->directory->id());
      $media_ids = $query->execute();

      // If directory has any media items, move them into root (remove value).
      if (!empty($media_ids)) {
        /** @var \Drupal\media\Entity\Media[] $media_items */
        $media_items = $this->entityTypeManager->getStorage('media')->loadMultiple($media_ids);

        foreach ($media_items as $media_item) {
          $media_item->get('directory')->setValue(NULL);
          $media_item->save();
        }
      }

      $parent_id = (int) $this->directory->get('parent')->target_id;

      // We use -1 as root folder tid.
      if ($parent_id === 0) {
        $parent_id = -1;
      }

      $this->directory->delete();

      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(new RefreshDirectoryTree($parent_id));
      // $response->addCommand(new AjaxLoadDirectory());
    }

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
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

}
