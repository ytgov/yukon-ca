<?php

namespace Drupal\yukon_w3_custom;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides translation status mapping for Content translations.
 */
trait TranslationStatuses {

  /**
   * Gets the internal mapping of translation statuses.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   * An array of translatable status labels keyed by status machine names.
   */
  private function translationStatusesMapping(): array {
    return [
      '' => new TranslatableMarkup('Any'),
      'absent' => new TranslatableMarkup('Absent'),
      'in_progress' => new TranslatableMarkup('In-progress'),
      'present' => new TranslatableMarkup('Present'),
      'out_dated' => new TranslatableMarkup('Out-dated'),
      'not_required' => new TranslatableMarkup('Not-required'),
    ];
  }

  /**
   * Retrieves all translation statuses.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   * An array of translation statuses.
   */
  public function getTranslationStatuses(): array {
    return $this->translationStatusesMapping();
  }

  /**
   * Retrieves the label for a specific translation status.
   *
   * @param string $status
   * The machine name of the status.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   * The translatable status label, or an empty string if the status is invalid.
   */
  public function getTranslationStatusLabel(string $status): TranslatableMarkup|string {
    $mapping = $this->translationStatusesMapping();
    return $mapping[$status] ?? '';
  }

}
