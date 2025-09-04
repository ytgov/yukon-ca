<?php

declare(strict_types=1);

namespace Drupal\yukon_hss_job_listings\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'field_ukg_pro_job_listings_formatter' field type formatter.
 */
#[FieldFormatter(
  id: "field_ukg_pro_job_listings_formatter",
  label: new TranslatableMarkup("Format the HSS UKG Pro job listings as a table."),
  field_types: ["field_ukg_pro_job_listings"],
)]
class UKGProJobListingsFormatter extends FormatterBase {

  /**
   * {@inheritDoc}
   *
   * Render the field XML contents into a table.
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    // Safely convert XML elements to renderable strings.
    $e2s = function ($element) {
      return html_entity_decode((string) $element);
    };

    // Column headings.
    $header[] = $langcode == 'fr' ? 'Catégorie' : 'Category';
    $header[] = $langcode == 'fr' ? [ 'data' => [ '#markup' => 'Numéro de<br>demande' ]] : 'Req. #';
    $header[] = $langcode == 'fr' ? 'Poste' : 'Job title';
    $header[] = $langcode == 'fr' ? 'Ministère' : 'Department';
    $header[] = $langcode == 'fr' ? 'Lieu' : 'Location';
    $header[] = $langcode == 'fr' ? 'Date limite' : 'Close date';

    // The rows of the render array as it's constructed.
    $rows = [];

    // TODO: Render API JSON results here.

    return [
      '#colgroups' => [],
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => FALSE,
      '#theme' => 'table',
      '#cache' => ['max-age' => 600],
      '#attributes' => ['id' => 'yukon-hss-ukg-pro-job-listings', 'class' => ['yukon-hss-job-listings']],
      '#attached' => ['library' => ['yukon_hss_job_listings/job_listings']],
    ];
  }

}
