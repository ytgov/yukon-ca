<?php

namespace Drupal\media_directories_editor\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\ImageStyleStorageInterface;
use Drupal\media\Plugin\Field\FieldFormatter\MediaThumbnailFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'media_directories_editor_thumbnail' formatter.
 *
 * @FieldFormatter(
 *   id = "media_directories_image_dimensions",
 *   label = @Translation("Image with dimensions"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaDirectoriesImageDimensionsFormatter extends MediaThumbnailFormatter {

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs an MediaThumbnailFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\image\ImageStyleStorageInterface $image_style_storage
   *   The image style entity storage handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, ImageStyleStorageInterface $image_style_storage, RendererInterface $renderer, ConfigFactoryInterface $config_factory, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage, $file_url_generator, $renderer);
    $this->configFactory = $config_factory;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('renderer'),
      $container->get('config.factory'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'dimensions' => [
        'image_width' => '',
        'image_height' => '',
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    if ($this->viewMode === '_entity_embed') {
      $storage = $form_state->getStorage();
      /** @var \Drupal\media\Entity\Media $entity */
      $entity = $storage['entity'];
      $element['#attached']['library'][] = 'media_directories_editor/image-resize';
      $element['image_link']['#access'] = FALSE;

      $config = $this->configFactory->get('media_directories_editor.settings');
      $styles = $element['image_style']['#options'];
      $selected_styles = $config->get('embed_dialog.image_styles');

      if (!empty($selected_styles)) {
        $styles = array_intersect_key($styles, $selected_styles);
      }

      $image_style_options[(string) $this->t('Pre-defined styles')] = $styles;

      $element['image_style']['#options'] = $image_style_options;
      $element['image_style']['#empty_option'] = $this->t('Custom dimensions');
      $element['image_style']['#description'] = $this->t('Choose from pre-defined image styles or set custom dimensions.');

      $element['dimensions'] = [
        '#type' => 'details',
        '#title' => $this->t('Image size'),
        '#description' => $this->t('Original image size: @widthx@height', [
          '@width' => $entity->get('thumbnail')->width,
          '@height' => $entity->get('thumbnail')->height,
        ]),
        '#open' => TRUE,
        '#attributes' => [
          'class' => ['media-directories-editor--dimensions'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="attributes[data-entity-embed-display-settings][image_style]"]' => ['value' => ''],
          ],
        ],
      ];

      $dimensions = $this->getSetting('dimensions');

      $img_width = empty($dimensions['image_width']) ? $entity->get('thumbnail')->width : $dimensions['image_width'];
      $img_height = empty($dimensions['image_height']) ? $entity->get('thumbnail')->height : $dimensions['image_height'];

      $element['dimensions']['image_width'] = [
        '#title' => t('Width'),
        '#type' => 'textfield',
        '#size' => 4,
        '#default_value' => $img_width,
        '#attributes' => [
          'class' => ['media-directories-editor--image-width'],
        ],
      ];

      $element['dimensions']['image_height'] = [
        '#title' => t('Height'),
        '#type' => 'textfield',
        '#size' => 4,
        '#default_value' => $img_height,
        '#attributes' => [
          'class' => ['media-directories-editor--image-height'],
        ],
      ];

      $element['dimensions']['controls'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['media-directories-editor--controls'],
        ],
        'reset' => [
          '#type' => 'html_tag',
          '#tag' => 'a',
          '#attributes' => [
            'class' => ['media-directories-editor--reset', 'button'],
            'data-width' => $entity->get('thumbnail')->width,
            'data-height' => $entity->get('thumbnail')->height,
          ],
          '#value' => $this->t('Reset'),
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $media_items = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($media_items)) {
      return $elements;
    }

    if ($this->getSetting('image_style')) {
      return parent::viewElements($items, $langcode);
    }

    /** @var \Drupal\media\MediaInterface[] $media_items */
    foreach ($media_items as $delta => $media) {
      /** @var \Drupal\file\Entity\File $file */
      $file = $media->get('thumbnail')->entity;

      $elements[$delta] = [
        '#theme' => 'image',
        '#attributes' => [
          'width' => $this->getSetting('dimensions')['image_width'],
          'height' => $this->getSetting('dimensions')['image_height'],
          'class' => [],
        ],
        '#uri' => $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()),
      ];

      // Add cacheability of each item in the field.
      $this->renderer->addCacheableDependency($elements[$delta], $media);
    }

    return $elements;
  }

}
