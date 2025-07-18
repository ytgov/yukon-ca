<?php

namespace Drupal\yukon_moderation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\Markup;
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

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $node->label()]) : $this->t('Revisions for %title', ['%title' => $node->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($this->currentUser()->hasPermission("revert $type revisions") || $this->currentUser()->hasPermission('revert all revisions') || $this->currentUser()->hasPermission('administer nodes')) && $node->access('update'));
    $delete_permission = (($this->currentUser()->hasPermission("delete $type revisions") || $this->currentUser()->hasPermission('delete all revisions') || $this->currentUser()->hasPermission('administer nodes')) && $node->access('delete'));

    $rows = [];
    $default_revision = $node->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach (array_reverse($node_storage->revisionIds($node)) as $vid) {
      $revision = $node_storage->loadRevision($vid);

      if (!$revision) {
        continue;
      }

      foreach ($revision->getTranslationLanguages() as $revision_langcode => $language) {
        $translated_revision = $revision->getTranslation($revision_langcode);

        if (!$translated_revision) {
          continue;
        }

        $username = [
          '#theme' => 'username',
          '#account' => $translated_revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($translated_revision->getRevisionCreationTime(), 'short');

        // We treat also the latest translation-affecting revision as current
        // revision, if it was the default revision, since its values for the
        // current language will be the same of the current default revision in
        // this case.
        $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $translated_revision->wasDefaultRevision());
        if (!$is_current_revision) {
          $link = Link::fromTextAndUrl($date, new Url('entity.node.revision', ['node' => $node->id(), 'node_revision' => $vid], ['language' => $language]));
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
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => Markup::create($this->renderer->render($link_renderable)),
              'username' => Markup::create($this->renderer->render($username_renderable)),
              'message' => Markup::create($translated_revision->getRevisionLogMessage()),
            ],
          ],
        ];

        // Add language info if multilingual.
        if ($has_translations) {
          $column['data']['#template'] = '{% trans %}{{ date }} by {{ username }} ({{ language }}){% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}';
          $column['data']['#context']['language'] = $language->getName();
        }

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

    $build['node_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attributes' => ['class' => 'node-revision-table'],
      '#empty' => $this->t('%type @entity does not have any revisions.', ['%type' => $node->getType(), '@entity' => $node->getEntityType()->getLabel()]),
      '#attached' => [
        'library' => ['node/drupal.node.admin'],
      ],
    ];

    // Add CSS to hide language switcher only on this page
    $build['#attached']['library'][] = 'yukon_moderation/hide_language_switcher';

    return $build;
  }

}
