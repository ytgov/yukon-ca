<?php

declare(strict_types=1);

namespace Drupal\yukon_hss_job_listings\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'field_hrsmart_job_listings' field type.
 */
#[FieldType(
  id: "field_hrsmart_job_listings",
  label: new TranslatableMarkup("HSS HRSmart Job Listings data"),
  description: [
    new TranslatableMarkup("Store HSS HRSmart job listings structured data."),
  ],
  category: "plain_text",
  default_widget: "string_textarea",
  default_formatter: "field_hrsmart_job_listings_formatter",
)]
final class HRSmartJobListingsItem extends StringLongItem {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    $values['value'] = <<<'EOD'
<jobs>
  <job id="987654321">
    <jobTitle>Nurse Practitioner</jobTitle>
    <requisitionNumber>987654321</requisitionNumber>
    <department>Health &amp; Social Services</department>
    <jobType>Posted</jobType>
    <locations>
      <location>
        <country>CA</country>
        <state>YT</state>
        <postalcode>Y1A 2C6</postalcode>
        <city>Whitehorse</city>
        <code>WH</code>
        <name>Whitehorse</name>
        <address_one>Box 2703</address_one>
        <address_two/>
      </location>
    </locations>
    <postedDate>2025-01-01 01:02:03</postedDate>
    <closedDate>2025-12-31</closedDate>
    <catexgories>
      <category>Healthcare</category>
    </catexgories>
    <url>https://yukon.ca/en/department-health-social-services</url>
  </job>
</jobs>
EOD;

    return $values;
  }

}
