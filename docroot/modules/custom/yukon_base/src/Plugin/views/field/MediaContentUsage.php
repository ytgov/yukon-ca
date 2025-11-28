<?php

namespace Drupal\yukon_base\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * A field handler to display content that uses a media entity.
 *
 * @ViewsField("media_content_usage")
 */
class MediaContentUsage extends FieldPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This field doesn't need to add anything to the query.
    // We'll do all the work in render().
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Get the entity type and ID from the file_usage row.
    $entity_type = $values->file_usage_type ?? NULL;
    $entity_id = $values->file_usage_id ?? NULL;

    // Only proceed if this is a media entity.
    if ($entity_type !== 'media' || empty($entity_id)) {
      return ['#markup' => ''];
    }

    try {
      // Load the media entity.
      $media = $this->entityTypeManager->getStorage('media')->load($entity_id);
      if (!$media) {
        return ['#markup' => ''];
      }

      // Find all nodes that reference this media entity.
      $nodes = $this->findReferencingNodes($media->id());

      if (empty($nodes)) {
        return ['#markup' => $this->t('Not used in content')];
      }

      // Build the output with each node on a separate line.
      $items = [];
      foreach ($nodes as $node) {
        $node_type_label = $node->type->entity->label();
        $items[] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $node_type_label . ': ',
          '#attributes' => ['style' => 'line-height: 1.5;'],
          'link' => [
            '#type' => 'link',
            '#title' => $node->getTitle(),
            '#url' => $node->toUrl(),
          ],
        ];
      }

      return [
        '#theme' => 'item_list',
        '#items' => $items,
        '#list_type' => 'ul',
        '#wrapper_attributes' => ['class' => ['media-content-usage-list']],
        '#attributes' => ['style' => 'list-style: none; padding-left: 0; margin: 0;'],
      ];
    }
    catch (\Exception $e) {
      \Drupal::logger('yukon_base')->error('Error in MediaContentUsage field: @message', ['@message' => $e->getMessage()]);
      return ['#markup' => ''];
    }
  }

  /**
   * Find all nodes that reference a given media entity.
   *
   * @param int $media_id
   *   The media entity ID.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Array of node entities that reference the media.
   */
  protected function findReferencingNodes($media_id) {
    $nodes = [];

    try {
      // Get all entity reference fields that target media.
      $field_map = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('entity_reference');

      // Look for node fields that reference media.
      if (isset($field_map['node'])) {
        foreach ($field_map['node'] as $field_name => $field_info) {
          // Check each bundle to see if this field references media.
          foreach ($field_info['bundles'] as $bundle) {
            $field_config = $this->entityTypeManager
              ->getStorage('field_config')
              ->load("node.{$bundle}.{$field_name}");

            if ($field_config && $field_config->getSetting('handler') === 'default:media') {
              // Query for nodes that reference this media entity.
              $query = $this->entityTypeManager->getStorage('node')->getQuery()
                ->condition($field_name, $media_id)
                ->accessCheck(TRUE)
                ->sort('changed', 'DESC');

              $nids = $query->execute();

              if (!empty($nids)) {
                $loaded_nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
                $nodes = array_merge($nodes, $loaded_nodes);
              }
            }
          }
        }
      }

      // Remove duplicates (in case a node references the same media in multiple fields).
      $unique_nodes = [];
      foreach ($nodes as $node) {
        $unique_nodes[$node->id()] = $node;
      }

      return $unique_nodes;
    }
    catch (\Exception $e) {
      \Drupal::logger('yukon_base')->error('Error finding referencing nodes: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }

}