<?php

namespace Drupal\cshs\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the field formatter.
 *
 * @FieldFormatter(
 *   id = "cshs_flexible_hierarchy",
 *   label = @Translation("Flexible hierarchy"),
 *   description = @Translation("Allows to specify the output with tokens."),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class CshsFlexibleHierarchyFormatter extends CshsFormatterBase {

  /**
   * An instance of the `token` service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->token = $container->get('token');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();
    $settings['format'] = '[term:name]';
    $settings['clear'] = TRUE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);
    $element['format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Format'),
      '#description' => $this->t('Specify a format for each field item by using tokens.'),
      '#default_value' => $this->getSetting('format'),
    ];

    $element['clear'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clear Tokens'),
      '#description' => $this->t('Remove token from final text if no replacement value is generated'),
      '#default_value' => $this->getSetting('clear'),
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['token_help'] = [
        '#type' => 'markup',
        '#theme' => 'token_tree_link',
        '#token_types' => ['term'],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Format: @format', ['@format' => $this->getSetting('format')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $linked = $this->getSetting('linked');
    $format = $this->getSetting('format');
    $clear = $this->getSetting('clear');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $term) {
      $text = $this->token->replace(
        $format,
        [
          'term' => $term,
        ],
        [
          'langcode' => $langcode,
          'clear' => $clear,
        ],
      );

      $elements[$delta]['#markup'] = $linked
        ? $term->toLink($text)->toString()
        : $text;
    }

    return $elements;
  }

}
