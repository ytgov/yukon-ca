<?php

namespace Drupal\yukon_moderation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Displays a list of all revisions across all languages with comparison.
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
   * Builds a form for revision comparison across all languages.
   */
  public function viewAllRevisions(NodeInterface $node) {
    if (!$node->access('view')) {
      throw new AccessDeniedHttpException();
    }
    return $this->formBuilder()->getForm('Drupal\yukon_moderation\Form\AllRevisionsComparisonForm', $node);
  }

  /**
   * Compare revisions with side-by-side view for cross-language comparison.
   */
  public function compareRevisions(NodeInterface $node, $left_revision, $right_revision, $left_langcode, $right_langcode) {
    if (!$node->access('view')) {
      throw new AccessDeniedHttpException();
    }

    $node_storage = $this->entityTypeManager()->getStorage('node');
    $left_revision_entity = $node_storage->loadRevision($left_revision);
    $right_revision_entity = $node_storage->loadRevision($right_revision);

    if (!$left_revision_entity || !$right_revision_entity) {
      throw new AccessDeniedHttpException();
    }

    $left_translation = $left_revision_entity->getTranslation($left_langcode);
    $right_translation = $right_revision_entity->getTranslation($right_langcode);

    // Determine if this is a cross-language comparison.
    $is_cross_language = $left_langcode !== $right_langcode;

    $left_date = $this->dateFormatter->format($left_translation->getRevisionCreationTime(), 'short');
    $right_date = $this->dateFormatter->format($right_translation->getRevisionCreationTime(), 'short');

    $build = [
      '#title' => $is_cross_language
        ? $this->t('Cross-language comparison: @left_lang vs @right_lang', [
          '@left_lang' => $left_translation->language()->getName(),
          '@right_lang' => $right_translation->language()->getName(),
        ])
        : $this->t('Comparing revisions @left and @right', [
          '@left' => $left_revision,
          '@right' => $right_revision,
        ]),
      '#attached' => [
        'library' => [
          'yukon_moderation/comparison',
          'yukon_moderation/hide_language_switcher',
        ],
      ],
    ];

    if ($is_cross_language) {
      $build['description'] = [
        '#markup' => '<p>' . $this->t('This is a cross-language comparison showing content side-by-side. Traditional diff is not applicable when comparing different languages.') . '</p>',
      ];
    }

    $build['comparison_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('@lang_name - Revision @rev (@date)', [
          '@lang_name' => $left_translation->language()->getName(),
          '@rev' => $left_revision,
          '@date' => $left_date,
        ]),
        $this->t('@lang_name - Revision @rev (@date)', [
          '@lang_name' => $right_translation->language()->getName(),
          '@rev' => $right_revision,
          '@date' => $right_date,
        ]),
      ],
      '#attributes' => [
        'class' => ['revision-comparison-table'],
      ],
    ];

    // Get the main content fields to compare.
    $fields_to_compare = ['title', 'body'];

    // Add other common fields if they exist.
    $field_definitions = $left_translation->getFieldDefinitions();
    foreach ($field_definitions as $field_name => $field_definition) {
      if (!in_array($field_name, $fields_to_compare) &&
          !$field_definition->isComputed() &&
          !in_array($field_name, [
            'nid',
            'vid',
            'type',
            'langcode',
            'status',
            'created',
            'changed',
            'promote',
            'sticky',
            'revision_timestamp',
            'revision_uid',
            'revision_log',
          ])) {
        $fields_to_compare[] = $field_name;
      }
    }

    foreach ($fields_to_compare as $field_name) {
      if ($left_translation->hasField($field_name) && $right_translation->hasField($field_name)) {
        $left_field = $left_translation->get($field_name);
        $right_field = $right_translation->get($field_name);

        if (!$left_field->isEmpty() || !$right_field->isEmpty()) {
          $build['comparison_table'][] = [
            'left' => [
              '#type' => 'container',
              '#attributes' => ['class' => ['revision-field-left']],
              'label' => [
                '#markup' => '<strong>' . $field_definitions[$field_name]->getLabel() . ':</strong>',
              ],
              'content' => $left_field->view(['label' => 'hidden']),
            ],
            'right' => [
              '#type' => 'container',
              '#attributes' => ['class' => ['revision-field-right']],
              'label' => [
                '#markup' => '<strong>' . $field_definitions[$field_name]->getLabel() . ':</strong>',
              ],
              'content' => $right_field->view(['label' => 'hidden']),
            ],
          ];
        }
      }
    }

    return $build;
  }

}
