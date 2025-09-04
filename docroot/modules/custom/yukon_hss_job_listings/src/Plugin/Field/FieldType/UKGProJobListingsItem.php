<?php

declare(strict_types=1);

namespace Drupal\yukon_hss_job_listings\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'field_ukg_pro_job_listings' field type.
 */
#[FieldType(
  id: "field_ukg_pro_job_listings",
  label: new TranslatableMarkup("HSS UKG Pro Job Listings data"),
  description: [
    new TranslatableMarkup("Store HSS UKG Pro job listings structured data."),
  ],
  category: "plain_text",
  default_widget: "string_textarea",
  default_formatter: "field_ukg_pro_job_listings_formatter",
)]
final class UKGProJobListingsItem extends StringLongItem {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    $values['value'] = <<<'EOD'
{
  'jobs': [
    'id': 987654321,
    'todo': 'FINISH THIS'
  ]
}
EOD;

    return $values;
  }

}
