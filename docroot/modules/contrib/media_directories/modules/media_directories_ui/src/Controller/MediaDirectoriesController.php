<?php

namespace Drupal\media_directories_ui\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormAjaxException;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\media_directories_ui\Form\MediaEditForm;
use Drupal\taxonomy\Entity\Term;
use Drupal\media_directories_ui\MediaDirectoriesUiHelper;
use Drupal\media_directories_ui\Ajax\RefreshDirectoryTree;
use Drupal\media_directories_ui\Form\DirectoryDeleteForm;
use Drupal\media_directories_ui\Form\MediaDeleteForm;
use Drupal\media_directories_ui\Ajax\LoadDirectoryContent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Main controller class.
 */
class MediaDirectoriesController extends ControllerBase {
  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorage
   */
  protected $termStorage;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The vocabulary id to use.
   *
   * @var string
   */
  protected $vocabularyId;

  /**
   * Indicator if content translation is enabled.
   *
   * @var bool
   */
  protected $contentTranslationEnabled;

  /**
   * Our helper service.
   *
   * @var \Drupal\media_directories_ui\MediaDirectoriesUiHelper
   */
  protected $mediaDirectoriesUiHelper;

  /**
   * MediaDirectoriesController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\media_directories_ui\MediaDirectoriesUiHelper $media_directories_ui_helper
   *   The media directories ui helper.
   */
  public function __construct(FormBuilder $formBuilder, RendererInterface $renderer, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, MediaDirectoriesUiHelper $media_directories_ui_helper) {
    $this->formBuilder = $formBuilder;
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;

    $config = $this->config('media_directories.settings');
    $this->vocabularyId = $config->get('directory_taxonomy');
    $this->contentTranslationEnabled = $this->moduleHandler->moduleExists('content_translation');
    $this->mediaDirectoriesUiHelper = $media_directories_ui_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('renderer'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('media_directories_ui.helper')
    );
  }

  /**
   * Return directory tree as JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function directoryTree() {
    $tree = [];
    $this->termStorage = $this->entityTypeManager()->getStorage('taxonomy_term');
    $terms = $this->termStorage->loadTree($this->vocabularyId);

    foreach ($terms as $term) {
      $this->buildTree($tree, $term, $this->vocabularyId);
    }

    $root_directory_name = $this->t('Root');
    if ($this->config('media_directories.settings')->get('all_files_in_root')) {
      $root_directory_name .=  ' (' . $this->t('All directories') . ')';
    }

    $tree = [
      [
        'id' => 'dir-root',
        'text' => $root_directory_name,
        'state' => [
          'opened' => TRUE,
          'selected' => TRUE,
        ],
        'a_attr' => [
          'data-tid' => MEDIA_DIRECTORY_ROOT,
        ],
        'children' => array_values($tree),
      ],
    ];

    return new JsonResponse($tree);
  }

  /**
   * Load directory content.
   *
   * Inserts directory content into browser.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function directoryContent(Request $request) {
    $response = new AjaxResponse();
    $directory_id = (int) $request->request->get('directory_id');
    $target_bundles = $request->request->all('target_bundles');
    $media_name_search = $request->request->get('media_name');

    $bundles = $target_bundles ? implode('+', $target_bundles) : 'all';
    $view = views_embed_view('media_directories_base', 'media_browser', $directory_id, $bundles, $media_name_search);

    $response->addCommand(new HtmlCommand('.browser--listing', $view));

    return $response;
  }

  /**
   * Create new directory.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function directoryAdd(Request $request) {
    $directory_id = (int) $request->request->get('parent_id');
    $directory_id = $directory_id === MEDIA_DIRECTORY_ROOT ? 0 : $directory_id;
    $name = $request->request->get('name');
    $current_language = $this->languageManager->getCurrentLanguage();
    $directory = Term::create([
      'name' => $name,
      'vid' => $this->vocabularyId,
      'parent' => [$directory_id],
      'langcode' => $current_language->getId(),
    ]);

    $data = [];
    if ($directory->access('create')) {
      $directory->save();

      $data = [
        'id' => 'dir-' . $directory->id(),
        'a_attr' => (object) ['data-tid' => $directory->id()],
        'text' => $directory->getName(),
      ];
    }

    return new JsonResponse($data);
  }

  /**
   * Rename directory.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function directoryRename(Request $request) {
    $directory_id = (int) $request->request->get('directory_id');
    $new_name = $request->request->get('directory_new_name');
    $directory = Term::load($directory_id);

    $response = new AjaxResponse();

    if ($directory->access('update')) {
      $directory->setName($new_name);
      $directory->save();
    }
    else {
      $this->messenger()->addError($this->t('No permission found to rename this directory.'));
      $this->addMessagesToResponse($response);
    }

    return $response;
  }

  /**
   * Move directory to different directory.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function directoryMove(Request $request) {
    $response = new AjaxResponse();
    $move_directory_id = (int) $request->request->get('move_directory_id');
    $to_directory_id = (int) $request->request->get('directory_id');
    $response->addCommand(new RefreshDirectoryTree($to_directory_id));

    // This shouldn't happen, but might cause issues when it does.
    if ($move_directory_id === $to_directory_id) {
      return $response;
    }

    /** @var \Drupal\taxonomy\Entity\Term $directory */
    $directory = $this->entityTypeManager()->getStorage('taxonomy_term')->load($move_directory_id);
    if ($directory->access('update')) {
      /** @var \Drupal\taxonomy\Entity\Term $new_parent_directory */
      $new_parent_directory = $this->entityTypeManager()->getStorage('taxonomy_term')->load($to_directory_id);
      if ($to_directory_id !== MEDIA_DIRECTORY_ROOT && $this->mediaDirectoriesUiHelper->termIsAnAnchestorOf($new_parent_directory, $directory)) {
        $this->messenger()->addError($this->t("You cannot move a directory to one of it's sub-directories!"));
        $this->addMessagesToResponse($response);
      } elseif ($this->mediaDirectoriesUiHelper->termIsAChildOf($directory, $new_parent_directory)) {
        $this->messenger()->addError($this->t('The directory is already a child of the destination directory!'));
        $this->addMessagesToResponse($response);
      } else {
        $directory->get('parent')->setValue($to_directory_id === MEDIA_DIRECTORY_ROOT ? NULL : $to_directory_id);
        $directory->save();
      }
    }
    else {
      $this->messenger()->addError($this->t('No permission found to move this directory.'));
      $this->addMessagesToResponse($response);
    }

    return $response;
  }

  /**
   * Delete directory.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function directoryDelete(Request $request) {
    $response = new AjaxResponse();

    $directory_id = (int) $request->request->get('directory_id');
    $target_bundles = $request->request->all('target_bundles');
    $directory = Term::load($directory_id);

    if ($directory === NULL) {
      return $response;
    }

    if ($directory->access('delete')) {
      $context = [
        'directory' => $directory,
        'target_bundles' => $target_bundles,
      ];
      $form = $this->formBuilder->getForm(DirectoryDeleteForm::class, $context);
      $response->addCommand(new OpenModalDialogCommand($this->t('Delete directory @name', ['@name' => $directory->getName()]), $form, ['width' => '500']));
    }
    else {
      $this->messenger()->addError($this->t('No permission found to delete this directory.'));
      $this->addMessagesToResponse($response);
    }

    return $response;
  }

  /**
   * New media entity add form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function mediaAdd(Request $request) {
    $response = new AjaxResponse();
    $ui_config = $this->config('media_directories_ui.settings');
    $combined_media_types =  ($ui_config->get('combined_upload_media_types') != NULL ? $ui_config->get('combined_upload_media_types') : []);
    $active_directory = (int) $request->get('active_directory', MEDIA_DIRECTORY_ROOT);
    $target_bundles = $request->get('target_bundles', []);
    $selection_mode = $request->get('selection_mode');
    $cardinality = $request->get('cardinality', -1);

    // Find the types combined upload should handle.
    // If types are limited and these are not allowed in combined upload,
    // then the tab is hidden.
    $combined_media_types_diff = array_intersect($target_bundles, $combined_media_types);

    if (!empty($combined_media_types_diff) && $ui_config->get('enable_combined_upload')) {
      $first_media_type = 'combined_upload';
    }
    else {
      $first_media_type = reset($target_bundles);
    }

    if (count($target_bundles) > 0) {
      if ($first_media_type != 'combined_upload' && $target_bundles) {
        // Here we land if no file is present.
        // Only list media types where the user has permission for.
        foreach ($target_bundles as $delta => $bundle) {
          if (!$this->entityTypeManager()->getAccessControlHandler('media')->createAccess($bundle)) {
            unset($target_bundles[$delta]);
          }
        }
        $first_media_type = reset($target_bundles);
        $selected_type = $request->get('media_type', $first_media_type);
      }
      else {
        // Here we land when a file was just picked by the user.
        $selected_type = $request->get('media_type', $first_media_type);
      }

      if (count($target_bundles) > 0) {
        $build = [
          '#theme' => 'media_directories_add',
          '#selected_type' => $selected_type,
          '#active_directory' => $active_directory,
          '#target_bundles' => $target_bundles,
          '#cardinality' => $cardinality,
          '#selection_mode' => $selection_mode,
        ];

        try {
          $response->addCommand(new OpenModalDialogCommand($this->t('Add media'), $build, ['width' => '800']));
        }
        catch (\Exception $e) {
          if ($e instanceof FormAjaxException) {
            // Make sure to re-throw the FormAjaxException!
            // This is needed to break for AJAX form processing.
            throw $e;
          }
          else {
            // fe. the OEmbed form can throw some errors if offline.
            $this->messenger()->addError($e->getMessage());
            $this->addMessagesToResponse($response, '.media-library-add-form-wrapper');
          }
        }
      }
      else {
        $this->messenger()->addError($this->t('No permission found for the creation of any media type.'));
        $this->addMessagesToResponse($response);
      }
    }
    else {
      $this->messenger()->addError($this->t('No media type seems to exist. <a href=":url">Add a new media type.</a>', [':url' => Url::fromRoute('entity.media_type.add_form')->toString()]));
      $this->addMessagesToResponse($response);
    }

    return $response;
  }

  /**
   * Media entity edit form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function mediaEdit(Request $request) {
    $response = new AjaxResponse();
    $media_items = $request->request->all('media_items', []);
    $active_directory = (int) $request->request->get('active_directory', MEDIA_DIRECTORY_ROOT);
    if (count($media_items) == 0) {
      // We're probably in the AJAX Form-Callback of the MediaEditForm (inheriting AddMediaFormBase)
      $media_items = $request->request->all('media', []);
      $media_ids = [];
      foreach ($media_items as $mid => $data) {
        // The nested array needs to be removed as nothing will not be loaded like this.
        $media_ids[$mid] = $mid;
      }
      $media_items = $media_ids;
    }
    $media_entities = $this->entityTypeManager()->getStorage('media')->loadMultiple($media_items);

    foreach ($media_entities as $mid => $media_entity) {
      if (!$media_entity->access('update')) {
        $this->messenger()->addError($this->t('Media %media_label of type %media_type cannot be edited due to lack of permissions.', [
          '%media_label' => $media_entity->label(),
          '%media_type' => $media_entity->bundle(),
        ]));
        unset($media_entities[$mid]);
      }
    }

    if (count($media_entities) > 0) {
      $form_state = new FormState();
      $form_state->set('media', $media_entities);
      $form_state->set('active_directory', $active_directory);

      $media_form = $this->formBuilder()->buildForm(MediaEditForm::class, $form_state);

      $this->addMessagesToForm($media_form);
      $response->addCommand(new OpenModalDialogCommand($this->t('Edit media'), $media_form, ['width' => '800']));
    }
    else {
      $this->addMessagesToResponse($response);
    }

    return $response;
  }

  /**
   * Move media to directory.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function mediaMove(Request $request) {
    $response = new AjaxResponse();
    $media_items = $request->request->all('media_items');
    $directory_id = (int) $request->request->get('directory_id');

    /** @var \Drupal\media\Entity\Media $media_entities */
    $media_entities = $this->entityTypeManager()->getStorage('media')->loadMultiple($media_items);

    foreach ($media_entities as $media_entity) {
      if ($media_entity->hasField('directory')) {
        if ($media_entity->access('update')) {
          $new_target_id = ($directory_id === MEDIA_DIRECTORY_ROOT ? NULL : $directory_id);
          if ($media_entity->get('directory')->target_id != $new_target_id) {
            $media_entity->get('directory')->setValue($new_target_id);
            $media_entity->save();
          }
          else {
            $this->messenger()->addError($this->t('The media is already inside the destination directory!'));
          }
        }
        else {
          $this->messenger()->addError($this->t('Media %media_label of type %media_type cannot be moved due to lack of permissions.', [
            '%media_label' => $media_entity->label(),
            '%media_type' => $media_entity->bundle(),
          ]));
        }
      }
    }

    $this->addMessagesToResponse($response);
    $response->addCommand(new LoadDirectoryContent());

    return $response;
  }

  /**
   * Media entity delete confirmation form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function mediaDelete(Request $request) {
    $response = new AjaxResponse();
    $media_items = $request->request->all('media_items', []);

    if (empty($media_items)) {
      return $response;
    }

    $media_entities = $this->entityTypeManager()->getStorage('media')->loadMultiple($media_items);

    foreach ($media_entities as $mid => $media_entity) {
      if (!$media_entity->access('delete')) {
        $this->messenger()->addError($this->t('Media %media_label of type %media_type cannot be deleted due to lack of permissions.', [
          '%media_label' => $media_entity->label(),
          '%media_type' => $media_entity->bundle(),
        ]));
        unset($media_entities[$mid]);
      }
    }

    if (count($media_entities) > 0) {
      $context = [
        'media_items' => $media_entities,
      ];

      $form = $this->formBuilder->getForm(MediaDeleteForm::class, $context);
      $this->addMessagesToForm($form);
      $response->addCommand(new OpenModalDialogCommand($this->t('Delete media'), $form, ['width' => '500']));
    }
    else {
      $this->addMessagesToResponse($response);
    }

    return $response;
  }

  /**
   * Populates a tree array given a taxonomy term tree object.
   *
   * @param array $tree
   *   An array representing the tree.
   * @param object $object
   *   A taxonomy term.
   * @param string $vocabulary
   *   The vocabulary id.
   */
  protected function buildTree(array &$tree, $object, $vocabulary) {
    if ($object->depth !== 0) {
      return;
    }

    $tree[$object->tid] = $object;
    $tree[$object->tid]->children = [];
    $tree[$object->tid]->text = $object->name;
    if ($this->contentTranslationEnabled) {
      $current_language = $this->languageManager->getCurrentLanguage();
      $term = Term::load($object->tid);
      if ($term && $term->hasTranslation($current_language->getId())) {
        $term = $term->getTranslation($current_language->getId());
        $tree[$object->tid]->text = $term->label();
      }
    }
    $tree[$object->tid]->a_attr = [
      'data-tid' => $object->tid,
    ];
    $tree[$object->tid]->id = 'dir-' . $object->tid;
    $object_children = &$tree[$object->tid]->children;

    $children = $this->termStorage->loadChildren($object->tid);

    if (!$children) {
      return;
    }

    $child_tree_objects = $this->termStorage->loadTree($vocabulary, $object->tid);

    foreach ($children as $child) {
      foreach ($child_tree_objects as $child_tree_object) {
        if ($child_tree_object->tid === $child->id()) {
          $this->buildTree($object_children, $child_tree_object, $vocabulary);
        }
      }
    }

    $tree[$object->tid]->children = array_values($tree[$object->tid]->children);
  }

  /**
   * Adds a PrependCommad to an ajax response rendering the current status messages.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The response.
   * @param string $selector
   *   A jQuery selector.
   */
  protected function addMessagesToResponse(AjaxResponse $response, $selector = '.entity-browser-form') {
    $status_messages = ['#type' => 'status_messages'];
    $messages = $this->renderer->renderRoot($status_messages);
    if (!empty($messages)) {
      $response->addCommand(new PrependCommand($selector, $messages));
    }
  }

  /**
   * Adds the current status messages on top of a form.
   *
   * @param array $form
   *   A from.
   */
  protected function addMessagesToForm(array $form) {
    $form['messages']['status'] = [
      '#type' => 'status_messages',
      '#weight' => -100,
    ];
  }

}
