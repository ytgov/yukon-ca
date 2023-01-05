<?php

namespace Drupal\cshs\Element;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\cshs\Component\CshsOption;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\views\Form\ViewsExposedForm;

/**
 * Defines the CSHS element.
 *
 * @FormElement("cshs")
 */
class CshsElement extends Select {

  public const ID = 'cshs';
  public const NONE_VALUE = 'All';
  public const NONE_LABEL = '- Please select -';

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();

    $info['#label'] = '';
    $info['#labels'] = [];
    // Works only with taxonomies and cannot be used if `#options` are
    // non-term values.
    $info['#force_deepest'] = FALSE;
    // Works only with taxonomies and cannot be used if `#options` are
    // non-term values.
    $info['#required_depth'] = 0;
    $info['#none_value'] = static::NONE_VALUE;
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    $info['#none_label'] = $this->t(static::NONE_LABEL);
    // Do not add the `<option value="#none_value">#none_label</option>`
    // to the first level selection.
    $info['#no_first_level_none'] = FALSE;
    $info['#theme'] = static::ID;
    $info['#process'][] = [static::class, 'processElement'];
    $info['#pre_render'][] = [static::class, 'preRender'];
    $info['#element_validate'][] = [static::class, 'validateElement'];

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return FALSE !== $input && NULL !== $input ? $input : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function processElement(array $element): array {
    \assert(Inspector::assertAllStringable($element['#labels']));
    // Make sure the `#none_value` doesn't overlap with other keys.
    \assert(!\array_key_exists($element['#none_value'], $element['#options']));
    // Make sure the `_none` option is added very at the beginning of a list.
    $element['#options'] = [$element['#none_value'] => new CshsOption($element['#none_label'])] + $element['#options'];
    $element['#attached']['library'][] = 'cshs/cshs.base';
    $element['#attached']['drupalSettings'][static::ID][$element['#id']] = [
      'labels' => $element['#labels'],
      'noneLabel' => $element['#none_label'],
      'noneValue' => (string) $element['#none_value'],
      'noFirstLevelNone' => $element['#no_first_level_none'],
    ];

    static::setAttributes($element, [
      'simpler-select-root',
      'form-element',
      'form-element--type-select',
    ]);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRender(array $element): array {
    if (isset($element['#empty_option'])) {
      $element += ['#empty_value' => ''];
      $element['#options'][$element['#empty_value']] = new CshsOption($element['#options'][$element['#empty_value']]);
    }

    $element['#options'] = CshsOption::formatOptions($element);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array &$element, FormStateInterface $form_state): void {
    $term_id = $element['#value'];

    if (\is_array($term_id)) {
      $term_id = \end($term_id);
    }

    // The value is not selected.
    /* @noinspection TypeUnsafeComparisonInspection */
    if (empty($term_id) || $term_id == $element['#none_value']) {
      // Element must have its `none` value when nothing selected. This will
      // let it function correctly, for instance with views. Otherwise, it
      // could lead to illegal choice selection error.
      /* @link https://www.drupal.org/node/2882790 */
      $form_state->setValueForElement($element, \is_a($form_state->getFormObject(), ViewsExposedForm::class) ? $element['#none_value'] : NULL);

      // Set an error if user doesn't select anything and field is required.
      if ($element['#required']) {
        $form_state->setError($element, \t('@label field is required.', [
          '@label' => $element['#label'],
        ]));
      }
    }
    // Do we want to force the user to select terms from the deepest level?
    elseif ($element['#force_deepest']) {
      $storage = static::getTermStorage();

      if (!$storage->load($term_id)) {
        $form_state->setError($element, \t('Unable to load a term (ID: @id) for the @label field.', [
          '@id' => $element['#value'],
          '@label' => $element['#label'],
        ]));
      }
      // Set an error if term has children.
      elseif (!empty($storage->loadChildren($term_id))) {
        $form_state->setError($element, \t('You need to select a term from the deepest level in @label field.', [
          '@label' => $element['#label'],
        ]));
      }
    }
    // Do we want to force a user to select terms from at least a certain level?
    elseif ($element['#required_depth'] > 0) {
      $storage = static::getTermStorage();

      if (\count($storage->loadAllParents($term_id)) < $element['#required_depth']) {
        $form_state->setError($element, \t('The field @label requires you to select at least @level levels of hierarchy.', [
          '@label' => $element['#label'],
          '@level' => $element['#required_depth'],
        ]));
      }
    }
  }

  /**
   * Returns the "taxonomy_term" entities storage.
   *
   * @return \Drupal\taxonomy\TermStorageInterface
   *   The storage.
   */
  protected static function getTermStorage(): TermStorageInterface {
    return \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  }

}
