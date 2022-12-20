<?php

namespace Drupal\blinker_addons_node\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class NodeViewAllLinksForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'blinker_addons_node.view_all_links',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blinker_addons_node_view_all_links_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('blinker_addons_node.view_all_links');

    $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
    foreach ($node_types as $node_type) {
      $title_key = $node_type->id() . '_title';
      $url_key = $node_type->id() . '_url';

      $form[$node_type->id()] = array(
        '#type' => 'details',
        '#title' => $node_type->label(),
        '#description' => t('Add All link title and url'),
        '#open' => TRUE,
        '#tree' => TRUE,
      );
      $form[$node_type->id()]['title'] = [
        '#type' => 'textfield',
        '#title' => 'Title',
        '#default_value' => $config->get($title_key) ? $config->get($title_key) : 'All ' . $node_type->label(),
      ];
      $form[$node_type->id()]['url'] = [
        '#type' => 'entity_autocomplete',
        '#title' => 'Url',
        '#target_type' => 'node',
        '#tags' => TRUE,
        '#default_value' =>  empty($config->get($url_key)) ? '' : Node::load($config->get($url_key)),
        '#selection_handler' => 'default',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();
    foreach ($values as $type => $value) {
      if (!is_array($value)) {
        continue;
      }

      if (!empty($value['title'])) {
        $this->config('blinker_addons_node.view_all_links')
          ->set($type . '_title', $value['title'])
          ->save();

        if (!empty($value['url'][0]['target_id'])) {
          $this->config('blinker_addons_node.view_all_links')
            ->set($type . '_url', $value['url'][0]['target_id'])
            ->save();
        }
      }
    }
  }

}
