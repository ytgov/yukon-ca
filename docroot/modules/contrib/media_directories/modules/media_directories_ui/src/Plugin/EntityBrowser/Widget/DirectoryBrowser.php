<?php

namespace Drupal\media_directories_ui\Plugin\EntityBrowser\Widget;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\Core\Url;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Uses a view to provide entity listing in a browser's widget.
 *
 * @EntityBrowserWidget(
 *   id = "media_directories_browser_widget",
 *   label = @Translation("Directory browser"),
 *   provider = "views",
 *   description = @Translation("Classical directory browsing."),
 *   auto_select = TRUE
 * )
 */
class DirectoryBrowser extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * The vocabulary id to use.
   *
   * @var string
   */
  protected $vocabularyId;

  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected  $themeManager;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('module_handler'),
      $container->get('config.factory'),
      $container->get('theme.manager')
    );
  }

  /**
   * Constructs a new View object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The active route match object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Theme manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, AccountInterface $current_user, RouteMatchInterface $route_match, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, ThemeManagerInterface $theme_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;

    if ($this->moduleHandler->moduleExists('content_translation')) {
      // I found no way to inject an optional service into this plugin.
      // @see https://symfony.com/doc/current/service_container/optional_dependencies.html
      // @see https://orkjern.com/services-with-optional-dependencies-drupal-8
      // @see https://www.md-systems.ch/en/blog/techblog/2016/12/17/how-to-safely-inject-additional-services-into-an-overridden-service
      $this->contentTranslationManager = \Drupal::service('content_translation.manager');
    }

    $config = $config_factory->get('media_directories.settings');
    $this->vocabularyId = $config->get('directory_taxonomy');
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    if (_media_directories_ui_library_file_exists('jstree')) {
      $form['#attached']['library'][] = 'media_directories_ui/jstree';
    }
    else {
      // Use CDN if jsTree library is not installed.
      $form['#attached']['library'][] = 'media_directories_ui/jstree-cdn';
    }
    $form['#attached']['library'][] = 'media_directories_ui/media-ui';

    switch ($this->themeManager->getActiveTheme()->getName()) {
      case 'claro':
        $form['#attached']['library'][] = 'claro/media_library.theme';
        break;
      case 'gin':
        $form['#attached']['library'][] = 'media_directories_ui/media-ui.browser.gin';
        break;
    }

    // Default values.
    $form['#attached']['drupalSettings']['media_directories']['cardinality'] = -1;
    $form['#attached']['drupalSettings']['media_directories']['target_bundles'] = [];

    // Routes for different actions.
    $form['#attached']['drupalSettings']['media_directories']['url'] = [
      'directory.tree' => Url::fromRoute('media_directories_ui.directory.tree')->toString(),
      'directory.content' => Url::fromRoute('media_directories_ui.directory.content')->toString(),
      'directory.add' => Url::fromRoute('media_directories_ui.directory.add')->toString(),
      'directory.rename' => Url::fromRoute('media_directories_ui.directory.rename')->toString(),
      'directory.delete' => Url::fromRoute('media_directories_ui.directory.delete')->toString(),
      'directory.move' => Url::fromRoute('media_directories_ui.directory.move')->toString(),
      'media.add' => Url::fromRoute('media_directories_ui.media.add')->toString(),
      'media.edit' => Url::fromRoute('media_directories_ui.media.edit')->toString(),
      'media.move' => Url::fromRoute('media_directories_ui.media.move')->toString(),
      'media.delete' => Url::fromRoute('media_directories_ui.media.delete')->toString(),
    ];

    // Pass taxonomy permissions.
    $vocabulary_permissions = [
      'create' => FALSE,
      'update' => FALSE,
      'translate' => FALSE,
      'delete' => FALSE,
    ];
    if (isset($this->vocabularyId)) {
      $directory = Term::create([
        'name' => 'will not be saved',
        'vid' => $this->vocabularyId,
      ]);
      $vocabulary_permissions['create'] = $directory->access('create');
      $vocabulary_permissions['update'] = $directory->access('update');
      $vocabulary_permissions['translate'] = $directory->access('translate');
      $vocabulary_permissions['delete'] = $directory->access('delete');
    }
    $form['#attached']['drupalSettings']['media_directories']['vocabulary_permissions'] = $vocabulary_permissions;

    // Decide the selection mode.
    $route_parameter_entity_browser_id = $this->routeMatch->getParameter('entity_browser_id');
    $selection_mode = 'reset';
    if ($route_parameter_entity_browser_id == 'media_directories_overview') {
      // We are on the media overview page.
      $selection_mode = 'keep';
    }
    $form['#attached']['drupalSettings']['media_directories']['selection_mode'] = $selection_mode;

    $cardinality = (int) NestedArray::getValue($form_state->getStorage(), [
      'entity_browser',
      'validators',
      'cardinality',
      'cardinality',
    ]);
    $remaining = (int) NestedArray::getValue($form_state->getStorage(), [
      'entity_browser',
      'widget_context',
      'remaining',
    ]);
    if ($route_parameter_entity_browser_id == 'media_directories_editor_browser') {
      // Allow only one item to be selected in the editor.
      $remaining = 1;
    }
    $target_bundles = NestedArray::getValue($form_state->getStorage(), [
      'entity_browser',
      'validators',
      'target_bundles',
    ]);

    if ($cardinality) {
      $form['#attached']['drupalSettings']['media_directories']['cardinality'] = $cardinality;
    }

    if ($remaining) {
      $form['#attached']['drupalSettings']['media_directories']['remaining'] = $remaining;
    }

    $enabled_bundles = [];
    if (isset($target_bundles['bundle']) && count($target_bundles['bundle']) > 0) {
      $enabled_bundles = $target_bundles['bundle'];
    }
    else {
      /** @var \Drupal\media\Entity\MediaType[] $types */
      $types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();

      foreach ($types as $type) {
        $enabled_bundles[$type->id()] = $type->id();
      }
    }

    $form['#attached']['drupalSettings']['media_directories']['target_bundles'] = $enabled_bundles;

    // Check wheater medias are translatable and pass on as javascript settings.
    foreach ($enabled_bundles as $type) {
      $media_translation_enabled = FALSE;
      if (isset($this->contentTranslationManager)) {
        $media_translation_enabled = $this->contentTranslationManager->isEnabled('media', $type);
      }
      $form['#attached']['drupalSettings']['media_directories']['media_translation_enabled'][$type] = $media_translation_enabled;
    }
    $term_translation_enabled = FALSE;
    if (isset($this->contentTranslationManager)) {
      $term_translation_enabled = $this->contentTranslationManager->isEnabled('taxonomy_term', $this->vocabularyId);
    }
    $form['#attached']['drupalSettings']['media_directories']['term_translation_enabled'] = $term_translation_enabled;

    $form['browser'] = [
      '#theme' => 'media_directories_browser',
    ];

    $form['browser']['active_directory'] = [
      '#type' => 'hidden',
      '#default_value' => MEDIA_DIRECTORY_ROOT,
    ];

    if ($this->configuration['entity_browser_id'] === 'media_directories_overview') {
      $form['actions']['#access'] = FALSE;
      $form['browser']['#attributes']['class'][] = 'media-browser--full';
    }
    else {
      $form['browser']['actions'] = $form['actions'];
      unset($form['actions']);
    }

    // When rebuilding makes no sense to keep checkboxes that were previously
    // selected.
    if (!empty($form['browser']['entity_browser_select'])) {
      foreach (Element::children($form['browser']['entity_browser_select']) as $child) {
        $form['browser']['entity_browser_select'][$child]['#process'][] = ['\Drupal\entity_browser\Plugin\EntityBrowser\Widget\View', 'processCheckbox'];
        $form['browser']['entity_browser_select'][$child]['#process'][] = ['\Drupal\Core\Render\Element\Checkbox', 'processAjaxForm'];
        $form['browser']['entity_browser_select'][$child]['#process'][] = ['\Drupal\Core\Render\Element\Checkbox', 'processGroup'];
      }
    }

    return $form;
  }

  /**
   * Sets the #checked property when rebuilding form.
   *
   * Every time when we rebuild we want all checkboxes to be unchecked.
   *
   * @see \Drupal\Core\Render\Element\Checkbox::processCheckbox()
   */
  public static function processCheckbox(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($form_state->isRebuilding()) {
      $element['#checked'] = FALSE;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    if (isset($user_input['entity_browser_select'])) {
      $selected_rows = array_values(array_filter($user_input['entity_browser_select']));

      foreach ($selected_rows as $row) {
        // Verify that the user input is a string and split it.
        // Each $row is in the format entity_type:id.
        if (is_string($row) && $parts = explode(':', $row, 2)) {
          // Make sure we have a type and id present.
          if (count($parts) == 2) {
            try {
              $storage = $this->entityTypeManager->getStorage($parts[0]);
              if (!$storage->load($parts[1])) {
                $message = $this->t('The @type Entity @id does not exist.', [
                  '@type' => $parts[0],
                  '@id' => $parts[1],
                ]);
                $form_state->setError($form['widget']['view']['entity_browser_select'], $message);
              }
            }
            catch (PluginNotFoundException $e) {
              $message = $this->t('The Entity Type @type does not exist.', [
                '@type' => $parts[0],
              ]);
              $form_state->setError($form['widget']['view']['entity_browser_select'], $message);
            }
          }
        }
      }

      // If there weren't any errors set, run the normal validators.
      if (empty($form_state->getErrors())) {
        $target_bundles = NestedArray::getValue($form_state->getStorage(), [
          'entity_browser',
          'validators',
          'target_bundles',
        ]);
        // Here we alter the form state to make sure all bundles are listed for the entity_browser validator,
        // if there is no target bundle selected in the button config. See #3193549.
        if (isset($target_bundles['bundle']) && count($target_bundles['bundle']) == 0) {
          /** @var \Drupal\media\Entity\MediaType[] $types */
          $types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();
          $all_bundles['bundle'] = [];
          foreach ($types as $type) {
            $all_bundles['bundle'][$type->id()] = $type->id();
          }
          $form_state->set(['entity_browser', 'validators', 'target_bundles'], $all_bundles);
        }

        parent::validate($form, $form_state);
      }
    }
    else {
      $form_state->setError($form, $this->t('Please select media element!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    $selected_rows = array_values(array_filter($form_state->getUserInput()['entity_browser_select']));
    $entities = [];
    foreach ($selected_rows as $row) {
      [$type, $id] = explode(':', $row);
      $storage = $this->entityTypeManager->getStorage($type);
      if ($entity = $storage->load($id)) {
        $entities[] = $entity;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $entities = $this->prepareEntities($form, $form_state);
    $this->selectEntities($entities, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues()['table'][$this->uuid()]['form'];
    $this->configuration['submit_text'] = $values['submit_text'];
    $this->configuration['auto_select'] = $values['auto_select'];
  }

}
