<?php

namespace Drupal\cshs;

use Drupal\Core\Entity\FieldableEntityStorageInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Html;
use Drupal\cshs\Component\CshsOption;
use Drupal\cshs\Element\CshsElement;
use Drupal\field\FieldStorageConfigInterface;

/**
 * @internal DO NOT refer to this constant in your code as it is most likely
 * to be removed.
 */
const HIERARCHY_OPTIONS = [
  'hierarchy_depth' => [
    'Hierarchy depth',
    [
      'Limits the nesting level. Use 0 to display all values. For the hierarchy like',
      '"a" -> "b" -> "c" the selection of 2 will result in "b" being the deepest option.',
    ],
  ],
  'required_depth' => [
    'Required depth',
    [
      'Requires item selection at the given nesting level. Use 0 to not impose the',
      'requirement. For the hierarchy like "a" -> "b" -> "c" the selection of 2 will',
      'obey a user to select at least "a" and "b".',
    ],
  ],
];

/**
 * Defines a class for getting options for a cshs form element from vocabulary.
 */
trait CshsOptionsFromHelper {

  use TaxonomyStorages;

  /**
   * Defines the default settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultSettings(): array {
    return [
      'parent' => '',
      'level_labels' => '',
      'force_deepest' => FALSE,
      'save_lineage' => FALSE,
      'hierarchy_depth' => 0,
      'required_depth' => 0,
      'none_label' => CshsElement::NONE_LABEL,
    ];
  }

  /**
   * Returns the array of settings, including defaults for missing settings.
   *
   * @return array
   *   The array of settings.
   */
  abstract public function getSettings(): array;

  /**
   * Returns the value of a setting, or its default value if absent.
   *
   * @param string $key
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  abstract public function getSetting($key): mixed;

  /**
   * Returns the list of taxonomy vocabularies IDs to work with.
   *
   * @return string[]|int[]
   *   The list of vocabularies IDs.
   */
  abstract public function getVocabulariesIds(): array;

  /**
   * Returns the taxonomy vocabularies to work with.
   *
   * @return \Drupal\taxonomy\VocabularyInterface[]
   *   The taxonomy vocabularies.
   */
  public function getVocabularies(): array {
    return $this->getVocabularyStorage()->loadMultiple($this->getVocabulariesIds());
  }

  /**
   * Returns a short summary for the settings.
   *
   * @return array
   *   A short summary of the settings.
   */
  public function settingsSummary(): array {
    $settings = $this->getSettings();
    $summary = [];
    $deepest = $this->t('Deepest');
    $none = $this->t('None');
    $yes = $this->t('Yes');
    $no = $this->t('No');

    $summary[] = $this->t('Parent: @parent', [
      '@parent' => empty($settings['parent']) ? $none : $this->getTranslationFromContext($this->getTermStorage()->load($settings['parent']))->label(),
    ]);

    foreach (HIERARCHY_OPTIONS as $option_name => [$title]) {
      /* @noinspection NestedTernaryOperatorInspection */
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $summary[] = $this->t("$title: @$option_name", [
        "@$option_name" => empty($settings['force_deepest'])
          ? (empty($settings[$option_name]) ? $none : $settings[$option_name])
          : $deepest,
      ]);
    }

    $summary[] = $this->t('Force deepest: @force_deepest', [
      '@force_deepest' => empty($settings['force_deepest']) ? $no : $yes,
    ]);

    $summary[] = $this->t('Save lineage: @save_lineage', [
      '@save_lineage' => empty($settings['save_lineage']) ? $no : $yes,
    ]);

    $summary[] = $this->t('Level labels: @level_labels', [
      '@level_labels' => empty($settings['level_labels']) ? $none : $this->getTranslatedLevelLabels(),
    ]);

    $summary[] = $this->t('The "no selection" label: @none_label', [
      '@none_label' => $this->getTranslatedNoneLabel(),
    ]);

    return $summary;
  }

  /**
   * Returns a form to configure settings.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form definition for the settings.
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element['parent'] = [
      '#type' => CshsElement::ID,
      '#title' => $this->t('Parent'),
      '#options' => $this->getOptions(),
      '#none_value' => 0,
      '#description' => $this->t('Select a parent term to use only a subtree of a vocabulary for this field.'),
      '#default_value' => $this->getSetting('parent'),
    ];

    foreach (HIERARCHY_OPTIONS as $option_name => [$title, $description]) {
      $description[] = '<i>Ignored when the deepest selection is enforced.</i>';
      $element[$option_name] = [
        '#min' => 0,
        '#type' => 'number',
        '#title' => $title,
        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        '#description' => $this->t(\implode('<br />', $description)),
        '#default_value' => $this->getSetting($option_name),
        '#states' => [
          'disabled' => [
            ':input[name*="force_deepest"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $element['force_deepest'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force the deepest level selection'),
      '#description' => $this->t('If checked the user will be forced to select terms from the deepest level.'),
      '#default_value' => $this->getSetting('force_deepest'),
    ];

    // This method can be called during Views filter configuration where
    // the "$this->fieldDefinition" is not available. Moreover, we don't
    // need to provide the "save_lineage" there.
    if ($this instanceof WidgetBase) {
      $errors = [];
      $field_storage = $this->fieldDefinition->getFieldStorageDefinition();
      \assert($field_storage instanceof FieldStorageConfigInterface);
      $entity_storage = $this->getStorage($field_storage->getTargetEntityTypeId());
      \assert($entity_storage instanceof FieldableEntityStorageInterface);
      $is_unlimited = $field_storage->getCardinality() === FieldStorageConfigInterface::CARDINALITY_UNLIMITED;
      $description = [
        'Save all parents of the selected term. Please note that you will not have a',
        'familiar field when multiple items can be added via the "Add more" button.',
        'In fact, the field will look like a "single" and the selected terms will',
        'be stored each as a separate field value.',
        '',
        '<b>Keep in mind</b> that this setting cannot be changed once the field storage',
        'gets at least one item. The restriction is imposed across all bundles of',
        'the "' . $entity_storage->getEntityType()->getLabel() . '" entity type.',
      ];

      if ($entity_storage->countFieldData($field_storage, TRUE)) {
        $errors[] = 'There is data for this field in the database. This setting can no longer be changed.';
      }

      if (!$is_unlimited) {
        $errors[] = 'The option can be enabled only for fields with unlimited cardinality.';
      }

      if (!empty($errors)) {
        \array_unshift($description, \sprintf('<b class="color-error">%s</b>', \implode('<br />', $errors)));
      }

      $element['save_lineage'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Save lineage'),
        '#disabled' => !empty($errors),
        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        '#description' => $this->t(\implode('<br />', $description)),
        '#default_value' => $is_unlimited && $this->getSetting('save_lineage'),
      ];
    }

    $element['level_labels'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Labels per hierarchy-level'),
      '#description' => $this->t('Enter labels for each hierarchy-level separated by comma.'),
      '#default_value' => $this->getTranslatedLevelLabels(),
    ];

    $element['none_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The "no selection" label'),
      '#description' => $this->t('The label for an empty option.'),
      '#default_value' => $this->getTranslatedNoneLabel(),
    ];

    $element['#element_validate'][] = [$this, 'validateSettingsForm'];

    return $element;
  }

  /**
   * Validates the settings form.
   *
   * @param array $element
   *   The element's form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateSettingsForm(array &$element, FormStateInterface $form_state): void {
    $settings = $form_state->getValue($element['#parents']);
    $options = $element['parent']['#options'];

    foreach ($options as $id => $option) {
      // This always removes at least the first item, which is what we
      // want. If a user selects nothing we remove the `- Please select -`
      // and count only the number of nesting levels. In another case,
      // we remove everything before and including the selected item and
      // count the rest.
      unset($options[$id]);
      // Leave the rest of the list after the selected option.
      if ((string) $id === $settings['parent']) {
        break;
      }
    }

    if ($settings['hierarchy_depth'] > ($max_hierarchy_depth = \count($options))) {
      $form_state->setError($element['hierarchy_depth'], $this->t('The hierarchy depth cannot be @actual because the selection list has @levels levels.', [
        '@actual' => $settings['hierarchy_depth'],
        '@levels' => $max_hierarchy_depth,
      ]));
    }
    elseif ($settings['required_depth'] > $settings['hierarchy_depth']) {
      $form_state->setError($element['required_depth'], $this->t('The required depth cannot be greater than the hierarchy depth.'));
    }
  }

  /**
   * Returns the form for a single widget.
   *
   * @return array
   *   The form elements for a single widget.
   */
  public function formElement(): array {
    $settings = $this->getSettings();

    if (!empty($settings['force_deepest']) || ($max_depth = $settings['hierarchy_depth']) < 1) {
      $max_depth = NULL;
    }

    return [
      '#type' => CshsElement::ID,
      '#labels' => $this->getTranslatedLevelLabels(FALSE),
      // The `parent` may be `null` after saving the form with no selection.
      '#options' => $this->getOptions((int) ($settings['parent'] ?? 0), $max_depth),
      '#multiple' => $settings['save_lineage'],
      '#none_label' => $this->getTranslatedNoneLabel(),
      '#default_value' => CshsElement::NONE_VALUE,
      '#force_deepest' => $settings['force_deepest'],
      '#required_depth' => $settings['required_depth'],
    ];
  }

  /**
   * Returns the list of options for `cshs` element.
   *
   * @param int $parent
   *   The ID of a parent term to start load children of.
   * @param int|null $max_depth
   *   The number of levels of the tree to return.
   *
   * @return \Drupal\cshs\Component\CshsOption[]
   *   Widget options.
   */
  private function getOptions(int $parent = 0, int $max_depth = NULL): array {
    $cache =& \drupal_static(__METHOD__, []);
    $cache_id = "$parent:$max_depth:" . \implode('-', $this->getVocabulariesIds());

    if (!isset($cache[$cache_id])) {
      $storage = $this->getTermStorage();
      $cache[$cache_id] = [];

      if ($this->needsTranslatedContent()) {
        $get_name = fn (object $term): string => $this->getTranslationFromContext($storage->load($term->tid))->label();
      }
      else {
        // Avoid loading the entity if we don't need its specific translation.
        $get_name = static fn (object $term): string => $term->name;
      }

      foreach ($this->getVocabularies() as $vocabulary) {
        // Historically vocabulary labels are not translatable
        // and the `t()` trick should be applied.
        $group = $this->getTranslatedValue($vocabulary->label());

        foreach ($storage->loadTree($vocabulary->id(), $parent, $max_depth, FALSE) as $term) {
          \assert($term instanceof \stdClass);
          \assert(\is_array($term->parents));
          \assert(\is_numeric($term->status));
          \assert(\is_numeric($term->depth));
          \assert(\is_numeric($term->tid));
          \assert(\is_string($term->name));

          // Allow only published terms.
          if ((bool) $term->status) {
            // The `parents` always has a value. In case there are no parents
            // the value is `['0']`. Check for an empty value just in case.
            $parent_tid = ((string) \reset($term->parents)) ?: '0';
            $cache[$cache_id][$term->tid] = new CshsOption($get_name($term), $parent_tid > 0 ? $parent_tid : NULL, $group);
          }
        }
      }
    }

    return $cache[$cache_id];
  }

  /**
   * Returns the translated labels with escaped markup.
   *
   * @param bool $return_as_string
   *   Whether returning value have to be a string.
   *
   * @return string|string[]
   *   The translated labels split by comma or an array of them.
   */
  private function getTranslatedLevelLabels(bool $return_as_string = TRUE): string|array {
    $labels = $this->getSetting('level_labels');

    if (empty($labels)) {
      return $return_as_string ? '' : [];
    }

    $labels = \array_map([$this, 'getTranslatedValue'], Tags::explode($labels));

    return $return_as_string ? \implode(', ', $labels) : $labels;
  }

  /**
   * Returns the translated label for the "no selection" option.
   *
   * @return string
   *   The label.
   */
  private function getTranslatedNoneLabel(): string {
    return $this->getTranslatedValue($this->getSetting('none_label') ?: CshsElement::NONE_LABEL);
  }

  /**
   * Returns the translated label.
   *
   * @param string $value
   *   The value for translate.
   *
   * @return string
   *   The translated value.
   */
  private function getTranslatedValue(string $value): string {
    $value = \trim($value);
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    return $value === '' ? $value : (string) $this->t(Html::escape($value));
  }

}
