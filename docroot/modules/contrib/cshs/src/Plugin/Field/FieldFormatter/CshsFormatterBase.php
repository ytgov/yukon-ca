<?php

namespace Drupal\cshs\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\cshs\IsApplicable;
use Drupal\cshs\TaxonomyStorages;
use Drupal\taxonomy\TermInterface;

/**
 * Base formatter for CSHS field.
 */
abstract class CshsFormatterBase extends EntityReferenceFormatterBase {

  use IsApplicable;
  use TaxonomyStorages;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'linked' => FALSE,
      'reverse' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = [];

    $element['linked'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to term page'),
      '#default_value' => $this->getSetting('linked'),
    ];

    $element['reverse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reverse order view'),
      '#default_value' => $this->getSetting('reverse'),
      '#description' => $this->t('Outputs hierarchy in reverse order (the deepest level first).'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $summary[] = $this->t('Linked to term page: @linked', [
      '@linked' => $this->getSetting('linked') ? $this->t('Yes') : $this->t('No'),
    ]);

    $summary[] = $this->t('Reverse order view: @reverse', [
      '@reverse' => $this->getSetting('reverse') ? $this->t('Yes') : $this->t('No'),
    ]);

    return $summary;
  }

  /**
   * Returns the list of terms labels.
   *
   * @param \Drupal\taxonomy\TermInterface[] $list
   *   The list of terms.
   * @param bool $linked
   *   The state of whether to create a linked label.
   *
   * @return string[]|\Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The list of labels.
   */
  protected function getTermsLabels(array $list, bool $linked): array {
    $terms = [];

    foreach ($list as $item) {
      $item = $this->getTranslationFromContext($item);
      $label = $item->label();
      $terms[] = $linked ? $item->toLink($label)->toString() : $label;
    }

    return $terms;
  }

  /**
   * Returns an array of all parents for a given term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The taxonomy term.
   * @param bool $start_from_root
   *   The state of whether to return the hierarchy starting from the root
   *   or vice versa.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The parent terms of a given term.
   */
  protected function getTermParents(TermInterface $term, bool $start_from_root = TRUE): array {
    $hierarchy = $this->getTermStorage()->loadAllParents($term->id());

    // By default, the `$hierarchy` ends by the root term.
    return $start_from_root ? \array_reverse($hierarchy) : $hierarchy;
  }

}
