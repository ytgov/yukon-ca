<?php

namespace Drupal\yukon_moderation\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for all revisions comparison page.
 */
class AllRevisionsComparisonForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The diff layout manager.
   *
   * @var \Drupal\diff\DiffLayoutManager|null
   */
  protected $diffLayoutManager;

  /**
   * Constructs an AllRevisionsComparisonForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
    DateFormatterInterface $date_formatter,
    RendererInterface $renderer,
    LanguageManagerInterface $language_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->languageManager = $language_manager;

    // Try to get diff layout manager if diff module is available.
    if (\Drupal::hasService('plugin.manager.diff.layout')) {
      $this->diffLayoutManager = \Drupal::service('plugin.manager.diff.layout');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'all_revisions_comparison_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $langname = $this->languageManager->getCurrentLanguage()->getName();
    $languages = $this->languageManager->getLanguages();
    $has_translations = (count($languages) > 1);

    $node_storage = $this->entityTypeManager->getStorage('node');
    $type = $node->getType();

    $form['#title'] = $this->t('All revisions for %title', ['%title' => $node->label()]);

    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $node->id(),
    ];

    $revert_permission = (($this->currentUser->hasPermission("revert $type revisions") || $this->currentUser->hasPermission('revert all revisions') || $this->currentUser->hasPermission('administer nodes')) && $node->access('update'));
    $delete_permission = (($this->currentUser->hasPermission("delete $type revisions") || $this->currentUser->hasPermission('delete all revisions') || $this->currentUser->hasPermission('administer nodes')) && $node->access('delete'));

    // Build revisions data.
    $revisions_data = $this->buildRevisionsData($node, $has_translations);
    $revision_count = count($revisions_data);

    $header = [$this->t('Revision'), $this->t('Language')];

    // Allow comparisons only if there are 2 or more revisions.
    if ($revision_count > 1) {
      $header += [
        'select_column_one' => '',
        'select_column_two' => '',
      ];
    }
    $header['operations'] = $this->t('Operations');

    // Submit button for the form.
    $compare_revision_submit = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Compare selected revisions'),
      '#attributes' => [
        'class' => [
          'diff-button',
        ],
      ],
    ];

    // For more than 5 revisions, add a submit button on top of the screen.
    if ($revision_count > 5) {
      $form['submit_top'] = $compare_revision_submit;
    }

    // Contains the table listing the revisions.
    $form['node_revisions_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => ['class' => ['diff-revisions', 'node-revision-table']],
    ];

    $form['node_revisions_table']['#attached']['library'][] = 'diff/diff.general';
    $form['node_revisions_table']['#attached']['library'][] = 'yukon_moderation/hide_language_switcher';
    $form['node_revisions_table']['#attached']['drupalSettings']['diffRevisionRadios'] = 'simple';

    $default_revision = $node->getRevisionId();
    $current_revision_displayed = FALSE;

    // Add rows to the table.
    foreach ($revisions_data as $key => $revision_info) {
      $revision = $revision_info['revision'];
      $translated_revision = $revision_info['translated_revision'];
      $language = $revision_info['language'];
      $vid = $revision->getRevisionId();

      $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $translated_revision->wasDefaultRevision());

      $username = [
        '#theme' => 'username',
        '#account' => $translated_revision->getRevisionUser(),
      ];

      $date = $this->dateFormatter->format($translated_revision->getRevisionCreationTime(), 'short');

      // Get the moderation status for this revision.
      $status = $this->t('Draft');

      // Try to get moderation state using Content Moderation API.
      if (\Drupal::moduleHandler()->moduleExists('content_moderation')) {
        /** @var \Drupal\content_moderation\ModerationInformationInterface $moderation_info */
        $moderation_info = \Drupal::service('content_moderation.moderation_information');
        if ($moderation_info->isModeratedEntity($translated_revision)) {
          $workflow = $moderation_info->getWorkflowForEntity($translated_revision);
          if ($workflow) {
            $current_state = $translated_revision->get('moderation_state')->value;
            if ($current_state) {
              $workflow_states = $workflow->getTypePlugin()->getStates();
              if (isset($workflow_states[$current_state])) {
                $status = $workflow_states[$current_state]->label();
              }
            }
          }
        }
      }

      // If content moderation didn't work, try basic published/unpublished.
      if ($status == $this->t('Draft')) {
        $status = $translated_revision->isPublished() ? $this->t('Published') : $this->t('Draft');
      }

      if (!$is_current_revision) {
        $link = Link::fromTextAndUrl($date, new Url('entity.node.revision', [
          'node' => $node->id(),
          'node_revision' => $vid,
        ], ['language' => $language]));
      }
      else {
        $link = $node->toLink($date, 'canonical', ['language' => $language]);
        $current_revision_displayed = TRUE;
      }

      $link_renderable = $link->toRenderable();
      $username_renderable = $username;

      $row = [];

      $column = [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}<p class="revision-log"> {% if message %} {{ message }} {% endif %}<span class="revision-status">({{ status }})</span></p>',
          '#context' => [
            'date' => Markup::create($this->renderer->render($link_renderable)),
            'username' => Markup::create($this->renderer->render($username_renderable)),
            'message' => Markup::create($translated_revision->getRevisionLogMessage()),
            'language' => $language->getName(),
            'status' => $status,
          ],
        ],
      ];

      $this->renderer->addCacheableDependency($column['data'], $translated_revision);

      $row[] = $column;

      $row[] = [
        'data' => [
          '#markup' => $language->getName(),
        ],
      ];

      // Allow comparisons only if there are 2 or more revisions.
      if ($revision_count > 1) {
        $unique_key = $vid . ':' . $language->getId();
        $row[] = $this->buildSelectColumn('radios_left', $unique_key, $key == 1 ? $unique_key : FALSE);
        $row[] = $this->buildSelectColumn('radios_right', $unique_key, $key == 0 ? $unique_key : FALSE);
      }

      $row_class = 'lang-' . $language->getId();

      if ($is_current_revision) {
        $row[] = [
          '#prefix' => '<em>',
          '#markup' => $this->t('Current revision'),
          '#suffix' => '</em>',
        ];
        $row_class .= ' revision-current';
      }
      else {
        $links = [];
        if ($revert_permission) {
          $links['revert'] = [
            'title' => $this->t('Revert'),
            'url' => Url::fromRoute('node.revision_revert_confirm', [
              'node' => $node->id(),
              'node_revision' => $vid,
            ], [
              'language' => $language,
            ]),
          ];
        }

        if ($delete_permission) {
          $links['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('node.revision_delete_confirm', [
              'node' => $node->id(),
              'node_revision' => $vid,
            ], [
              'language' => $language,
            ]),
          ];
        }

        $row[] = [
          '#type' => 'operations',
          '#links' => $links,
        ];
      }

      $form['node_revisions_table'][] = [
        '#attributes' => ['class' => [$row_class]],
      ] + $row;
    }

    // Allow comparisons only if there are 2 or more revisions.
    if ($revision_count > 1) {
      $form['submit'] = $compare_revision_submit;
    }

    $form['#attached']['library'][] = 'node/drupal.node.admin';
    return $form;
  }

  /**
   * Set column attributes and return config array.
   *
   * @param string $name
   *   Name attribute.
   * @param string $return_val
   *   Return value attribute.
   * @param string $default_val
   *   Default value attribute.
   *
   * @return array
   *   Configuration array.
   */
  protected function buildSelectColumn($name, $return_val, $default_val) {
    return [
      '#type' => 'radio',
      '#title_display' => 'invisible',
      '#name' => $name,
      '#return_value' => $return_val,
      '#default_value' => $default_val,
    ];
  }

  /**
   * Builds the revisions data - similar to original returns structured data.
   */
  protected function buildRevisionsData(NodeInterface $node, $has_translations) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $revisions_data = [];

    foreach (array_reverse($node_storage->revisionIds($node)) as $vid) {
      $revision = $node_storage->loadRevision($vid);

      if (!$revision) {
        continue;
      }

      // Check each configured language to see if this revision has content.
      $available_languages = $this->languageManager->getLanguages();
      foreach ($available_languages as $langcode => $language) {
        // Check if translation exists for this specific revision.
        if (!$revision->hasTranslation($langcode)) {
          continue;
        }

        $translated_revision = $revision->getTranslation($langcode);

        if (!$translated_revision) {
          continue;
        }

        // Additional check: verify this translation has actual content changes.
        // Skip if this translation doesn't have meaningful content changes.
        if (!$translated_revision->isRevisionTranslationAffected()) {
          continue;
        }

        $revisions_data[] = [
          'revision' => $revision,
          'translated_revision' => $translated_revision,
          'language' => $language,
        ];
      }
    }

    return $revisions_data;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();

    $revisions = $form_state->getValue('node_revisions_table');
    if (!is_countable($revisions) || count($revisions) <= 1) {
      $form_state->setErrorByName('node_revisions_table', $this->t('Multiple revisions are needed for comparison.'));
    }
    elseif (!isset($input['radios_left']) || !isset($input['radios_right'])) {
      $form_state->setErrorByName('node_revisions_table', $this->t('Select two revisions to compare.'));
    }
    elseif ($input['radios_left'] == $input['radios_right']) {
      $form_state->setErrorByName('node_revisions_table', $this->t('Select different revisions to compare.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $vid_left = $input['radios_left'];
    $vid_right = $input['radios_right'];
    $nid = $input['nid'];

    // Parse the revision IDs from the format 'vid:langcode'.
    [$left_vid, $left_langcode] = explode(':', $vid_left);
    [$right_vid, $right_langcode] = explode(':', $vid_right);

    // Always place the older revision on the left side of the comparison.
    if ($left_vid > $right_vid) {
      $aux_vid = $left_vid;
      $aux_langcode = $left_langcode;
      $left_vid = $right_vid;
      $left_langcode = $right_langcode;
      $right_vid = $aux_vid;
      $right_langcode = $aux_langcode;
    }

    // Check if we're comparing different languages.
    if ($left_langcode !== $right_langcode) {
      // Use our custom cross-language comparison.
      $redirect_url = Url::fromRoute(
        'yukon_moderation.compare_revisions',
        [
          'node' => $nid,
          'left_revision' => $left_vid,
          'right_revision' => $right_vid,
          'left_langcode' => $left_langcode,
          'right_langcode' => $right_langcode,
        ],
      );
    }
    // Same language comparison - use diff module if available.
    elseif (\Drupal::moduleHandler()->moduleExists('diff') && $this->diffLayoutManager) {
      // Both revisions are in the same language, use that language.
      $comparison_language = $this->languageManager->getLanguage($left_langcode);

      // Use diff module's route with proper default layout and lang context.
      $redirect_url = Url::fromRoute(
        'diff.revisions_diff',
        [
          'node' => $nid,
          'left_revision' => $left_vid,
          'right_revision' => $right_vid,
          'filter' => $this->diffLayoutManager->getDefaultLayout(),
        ],
        [
          'language' => $comparison_language,
        ]
      );
    }
    else {
      // Fallback for same language comparison without diff module.
      $redirect_url = Url::fromRoute(
        'yukon_moderation.compare_revisions',
        [
          'node' => $nid,
          'left_revision' => $left_vid,
          'right_revision' => $right_vid,
          'left_langcode' => $left_langcode,
          'right_langcode' => $right_langcode,
        ],
      );
    }

    $form_state->setRedirectUrl($redirect_url);
  }

}
