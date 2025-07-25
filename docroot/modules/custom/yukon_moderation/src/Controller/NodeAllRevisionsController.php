<?php

namespace Drupal\yukon_moderation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays a raw list of all revisions across all languages.
 */
class NodeAllRevisionsController extends ControllerBase {

  /**
   * The date formatter service.
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
   * Constructs a NodeAllRevisionsController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Builds a raw revisions table.
   */
  public function viewAllRevisions(NodeInterface $node) {
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    $langname = $this->languageManager()->getCurrentLanguage()->getName();
    $languages = $this->languageManager()->getLanguages();
    $has_translations = (count($languages) > 1);

    $node_storage = $this->entityTypeManager()->getStorage('node');
    $type = $node->getType();

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $node->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $node->label()]);

    // Build simple revisions table without comparison functionality.
    $build['node_revisions_table'] = $this->buildRevisionsTable($node, $has_translations);
    $build['#attached'] = [
      'library' => ['node/drupal.node.admin'],
    ];

    // Add CSS to hide language switcher only on this page.
    $build['#attached']['library'][] = 'yukon_moderation/hide_language_switcher';

    return $build;
  }

  /**
   * Builds the revisions table - simplified version.
   */
  protected function buildRevisionsTable(NodeInterface $node, $has_translations) {
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $type = $node->getType();

    $revert_permission = (($this->currentUser()->hasPermission("revert $type revisions") || $this->currentUser()->hasPermission('revert all revisions') || $this->currentUser()->hasPermission('administer nodes')) && $node->access('update'));
    $delete_permission = (($this->currentUser()->hasPermission("delete $type revisions") || $this->currentUser()->hasPermission('delete all revisions') || $this->currentUser()->hasPermission('administer nodes')) && $node->access('delete'));

    $header = [$this->t('Revision'), $this->t('Operations')];
    $rows = [];
    $default_revision = $node->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach (array_reverse($node_storage->revisionIds($node)) as $vid) {
      $revision = $node_storage->loadRevision($vid);

      if (!$revision) {
        continue;
      }

      // Check each configured language to see if this revision has content.
      $available_languages = $this->languageManager()->getLanguages();
      foreach ($available_languages as $langcode => $language) {
        // Check if translation exists for this specific revision.
        if (!$revision->hasTranslation($langcode)) {
          continue;
        }

        $translated_revision = $revision->getTranslation($langcode);

        if (!$translated_revision) {
          continue;
        }

        // Additional check: verify this translation has actual content.
        // Skip if this translation doesn't have meaningful content changes.
        if (!$translated_revision->isRevisionTranslationAffected()) {
          continue;
        }

        $username = [
          '#theme' => 'username',
          '#account' => $translated_revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
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

        // We treat also the latest translation-affecting revision as current
        // revision, if it was the default revision, since its values for the
        // current language will be the same of the current default revision in
        // this case.
        $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $translated_revision->wasDefaultRevision());

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
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}<p class="revision-status">({{ language }} - {{ status }})</p>{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
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

        if ($is_current_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];

          $row['#attributes'] = [
            'class' => ['revision-current'],
          ];
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
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    return [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attributes' => ['class' => 'node-revision-table'],
      '#empty' => $this->t('%type @entity does not have any revisions.', [
        '%type' => $node->getType(),
        '@entity' => $node->getEntityType()->getLabel(),
      ]),
    ];
  }

}
