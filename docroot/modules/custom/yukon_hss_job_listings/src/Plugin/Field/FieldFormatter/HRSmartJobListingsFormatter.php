<?php

declare(strict_types=1);

namespace Drupal\yukon_hss_job_listings\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'field_hrsmart_job_listings_formatter' field type formatter.
 */
#[FieldFormatter(
  id: "field_hrsmart_job_listings_formatter",
  label: new TranslatableMarkup("Format the HRSmart job listings as a table."),
  field_types: ["field_hrsmart_job_listings"],
)]
class HRSmartJobListingsFormatter extends FormatterBase {

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
    $header[] = $langcode == 'en' ? 'Category' : 'Catégorie';
    $header[] = $langcode == 'en' ? 'Req. #': 'Numéro de demande';
    $header[] = $langcode == 'en' ? 'Job title': 'Poste';
    $header[] = $langcode == 'en' ? 'Department': 'Ministère';
    $header[] = $langcode == 'en' ? 'Location': 'Lieu';
    $header[] = $langcode == 'en' ? 'Close date': 'Date limite';

    // The rows of the render array as it's constructed.
    $rows = [];

    if (count($items) >= 1) {
      // @noinspection PhpComposerExtensionStubsInspection
      $xml = simplexml_load_string((string) $items[0]->value);

      if ($xml !== FALSE) {
        $jobs = $xml->job;

        if (count($jobs) > 0) {
          foreach ($jobs as $job) {
            $row = [];

            // Category.
            $categoryList = [];
            $categories = $job->categories->category;

            foreach ($categories as $category) {
              $categoryList[] = $e2s($category);
            }

            $row[] = implode(', ', $categoryList);

            // Requisition #.
            $requisitionNumber = (int) $job->requisitionNumber;
            $row[] = $e2s($requisitionNumber);

            // Job title.
            $jobTitle = $e2s($job->jobTitle);
            $apply_url = (string) $job->url;
            $row[] = [
              'data' => [
                '#markup' => '<a href="' . $apply_url . '">' . $jobTitle . '</a>',
              ],
            ];

            // Department.
            $row[] = $e2s($job->department);

            // Location.
            $locations = $job->locations->location;
            $location = $locations[0];
            $city = $e2s($location->city);
            $territory = $e2s($location->state);
            $country = $e2s($location->country);
            $postalCode = $e2s($location->postalCode);
            $row[] = "$city, $territory, $country $postalCode";

            // Posted date.
            $closedDate = strtotime((string) $job->closedDate);
            $row[] = [
              'data' => date('j F Y', $closedDate),
              'class' => 'nowrap',
            ];

            $rows[] = $row;
          }
        }
      }
    }

    return [
      '#colgroups' => [],
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => FALSE,
      '#theme' => 'table',
      '#cache' => ['max-age' => 600],
      '#attributes' => ['id' => 'yukon-hss-hrsmart-job-listings', 'class' => ['yukon-hss-job-listings']],
      '#attached' => ['library' => ['yukon_hss_job_listings/job_listings']],
    ];
  }

}
