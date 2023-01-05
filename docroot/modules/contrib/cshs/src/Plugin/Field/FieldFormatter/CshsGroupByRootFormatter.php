<?php

namespace Drupal\cshs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the field formatter.
 *
 * @FieldFormatter(
 *   id = "cshs_group_by_root",
 *   label = @Translation("Hierarchy grouped by root"),
 *   description = @Translation("Display the hierarchy of the taxonomy term grouped by root."),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 *
 * @example
 * The result of a multivalued selection, grouped by root.
 * @code
 * Given:
 *   field_tags.0: a, b, c
 *   field_tags.1: a, b1
 *   field_tags.2: a, b, c, d
 *   field_tags.3: a1, b1, c1
 *   field_tags.4: a1, b1, c1, d1
 *
 * Result (reverse=FALSE):
 *   a
 *     - b
 *     - c
 *     - b1
 *     - d
 *   a1
 *     - b1
 *     - c1
 *     - d1
 *
 * Result (reverse=TRUE):
 *   c
 *     - b
 *     - a
 *   b1
 *     - a
 *   d
 *     - c
 *     - b
 *     - a
 *   c1
 *     - b1
 *     - a1
 *   d1
 *     - c1
 *     - b1
 *     - a1
 * @code
 */
class CshsGroupByRootFormatter extends CshsFormatterBase {

  /**
   * The option to disable sorting.
   */
  public const SORT_NONE = 'none';

  /**
   * The option to sort children descendingly.
   */
  public const SORT_DESC = 'desc';

  /**
   * The option to sort children ascendingly.
   */
  public const SORT_ASC = 'asc';

  /**
   * The list of all available sorting variants.
   */
  protected const SORT_OPTIONS = [
    self::SORT_NONE => 'None',
    self::SORT_DESC => 'Descending',
    self::SORT_ASC => 'Ascending',
  ];

  /**
   * The list of PHP functions that sort an array by key.
   *
   * NOTES:
   *   - the function must modify the argument by reference;
   *   - the function argument must be an associative array because sorting
   *     happens by keys.
   *
   * @see \krsort()
   * @see \ksort()
   */
  protected const SORT_FUNCTIONS = [
    self::SORT_DESC => '\krsort',
    self::SORT_ASC => '\ksort',
  ];

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();
    $settings['sort'] = static::SORT_NONE;
    $settings['depth'] = 0;
    $settings['last_child'] = FALSE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);
    $element['sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Sorting'),
      '#options' => \array_map([$this, 't'], static::SORT_OPTIONS),
      '#default_value' => $this->getSetting('sort'),
    ];

    $element['depth'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Depth'),
      '#description' => $this->t('The maximum hierarchy depth. Use 0 to not limit or 1 to display just the root term (or the last if reverse order selected).'),
      '#default_value' => $this->getSetting('depth'),
      '#states' => [
        'disabled' => [
          ':input[name*="last_child"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['last_child'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Last child'),
      '#description' => $this->t('Show only the root term and its deepest child.'),
      '#default_value' => $this->getSetting('last_child'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Sorting: @value', [
      '@value' => static::SORT_OPTIONS[$this->getSetting('sort')],
    ]);

    /* @noinspection NestedTernaryOperatorInspection */
    $summary[] = $this->t('Depth: @value', [
      '@value' => $this->getSetting('last_child')
        ? $this->t('Last child only')
        : ($this->getSetting('depth') ?: $this->t('Unlimited')),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $depth = (int) $this->getSetting('depth');
    $linked = (bool) $this->getSetting('linked');
    $reverse = (bool) $this->getSetting('reverse');
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $term) {
      $tree = $this->getTermParents($term, !$reverse);

      if ($depth > 0) {
        // @todo This is not an optimal solution as we may load lots of entities that won't be used.
        $tree = \array_slice($tree, 0, $depth, TRUE);
      }

      $labels = $this->getTermsLabels($tree, $linked);
      $root = \array_shift($tree);
      $root_id = $root->id();
      $root_label = \array_shift($labels);

      if (!isset($elements[$root_id])) {
        $elements[$root_id] = [
          '#theme' => 'cshs_term_group',
          '#id' => $root_id,
          '#title' => $root_label,
          '#terms' => [],
        ];
      }

      // Replace calls to `\array_merge()` and `\array_unique()` by the loop.
      foreach ($labels as $i => $label) {
        $unique_key = (string) $tree[$i]->label();

        if (!isset($elements[$root_id]['#terms'][$unique_key])) {
          $elements[$root_id]['#terms'][$unique_key] = [
            'id' => $tree[$i]->id(),
            'label' => $label,
            'parent' => $tree[$i]->parent->target_id,
          ];
        }
      }
    }

    if ($sort_function = static::SORT_FUNCTIONS[$this->getSetting('sort')] ?? NULL) {
      foreach ($elements as $root_id => $element) {
        $sort_function($elements[$root_id]['#terms']);
      }
    }

    if ($this->getSetting('last_child')) {
      foreach ($elements as $root_id => $element) {
        $value = \end($element['#terms']);
        $key = \key($element['#terms']);

        $elements[$root_id]['#terms'] = [
          $key => $value,
        ];
      }
    }

    return $elements;
  }

}
