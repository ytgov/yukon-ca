<?php

/**
 * @file
 * Builds placeholder replacement tokens for yukon.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function yukon_base_token_info(): array {
  $info['tokens']['current-page']['bilingual_default_value'] = [
    'name' => t('Metatag: Default Metatag value'),
    'description' => t('Bilingual default value for use with WetKit specific metatags'),
  ];
  $info['tokens']['current-page']['bilingual_prefix'] = [
    'name' => t('Metatag: Bilingual Prefix'),
    'description' => t('Bilingual prefix for use with Metatags'),
  ];
  $info['tokens']['node']['improved_summary'] = [
    'name' => t('Metatag: Improved Summary'),
    'description' => t('Improved Summary for use with Metatags'),
  ];
  $info['tokens']['node']['bilingual_date_published'] = [
    'name' => t('Metatag: Bilingual Date Published'),
    'description' => t('Bilingual Date Published for use with Metatags'),
  ];
  $info['tokens']['node']['field_department_term_en'] = [
    'name' => t('Metatag: Department, always in English'),
    'description' => t('English value of field_department_term'),
  ];

  return [
    'types' => $info,
  ];
}

/**
 * Implements hook_tokens().
 */
function yukon_base_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  if (isset($options['langcode'])) {
    $language_code = $options['langcode'];
  }

  $replacements = [];

  if ($type == 'node') {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $data['node'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'improved_summary':
          $replacements[$original] = $node->hasField('body') ? $node->get('body')->value . '...' : '...';
          break;

        case 'bilingual_date_published':
          $published_time = $node->getCreatedTime();
          $replacements[$original] = \Drupal::service('date.formatter')->format($published_time, 'custom', 'Y-m-d');
          break;

        case 'field_department_term_en':
          if(!$node->hasField('field_department_term')) {
            break;
          }
          // From SO
          //   https://stackoverflow.com/questions/37122908/drupal-8-get-taxonomy-term-value-in-node
          //   https://drupal.stackexchange.com/questions/205972/how-do-i-load-taxonomy-terms
          $term_id = $node->get('field_department_term')->target_id;
          if(empty($term_id)) {
            break;
          }
          // Load the term with no language context, gets the original.
          $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term_id);
          if(empty($term)){
            break;
          }
          $replacements[$original] = $term->getName();
          break;
      }
    }
  }

  if ($type == 'current-page') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'bilingual_default_value':
          $replacements[$original] = ($language_code === 'fr') ? 'nul' : 'none';
          break;

        case 'bilingual_prefix':
          $replacements[$original] = ($language_code === 'fr') ? 'fra' : 'eng';
          break;
      }
    }
  }
  return $replacements;
}
