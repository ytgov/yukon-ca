<?php

namespace Drupal\addtoany\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Provides an 'AddToAny share' block.
 *
 * @Block(
 *   id = "addtoany_block",
 *   admin_label = @Translation("AddToAny share buttons"),
 * )
 */
class AddToAnyBlock extends BlockBase {

  /**
   * The AddToAny option keys used in this block.
   */
  private $addtoany_option_keys = [
    'link_url',
    'link_title',
    'buttons_size',
    'addtoany_html',
  ];

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $node_id = \Drupal::routeMatch()->getParameter('node');
    if (is_numeric($node_id)) {
      $node = Node::load($node_id);
    }

    $is_node = isset($node) && $node instanceof NodeInterface ? true : false;
    $data = $is_node ? addtoany_create_entity_data($node) : addtoany_create_data();

    $block_options = [];
    foreach ($this->addtoany_option_keys as $key) {
      $block_options[$key] = $this->configuration[$key] ?: $data[$key];
    }

    $build = [
      '#addtoany_html'              => $block_options['addtoany_html'],
      '#link_url'                   => $block_options['link_url'],
      '#link_title'                 => $block_options['link_title'],
      '#button_setting'             => $data['button_setting'],
      '#button_image'               => $data['button_image'],
      '#universal_button_placement' => $data['universal_button_placement'],
      '#buttons_size'               => $block_options['buttons_size'],
      '#theme'                      => 'addtoany_standard',
      '#cache'                      => [
        'contexts' => ['url'],
      ],
    ];

    if ($is_node) {
      $build['#addtoany_html'] = \Drupal::token()->replace($data['addtoany_html'], ['node' => $node]);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configs = [];
    foreach ($this->addtoany_option_keys as $key) {
      $configs[$key] = '';
    }
    return $configs;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form_in_use = FALSE;
    foreach ($this->addtoany_option_keys as $key) {
      $option = $this->configuration[$key];
      if (isset($option) && $option !== '') {
         $form_in_use = TRUE;
         break;
      }
    }

    $attributes_for_code = [
      'autocapitalize' => ['off'],
      'autocomplete' => ['off'],
      'autocorrect' => ['off'],
      'spellcheck' => ['false'],
    ];

    $form['addtoany_options'] = [
      '#type'         => 'details',
      '#title'        => $this->t('AddToAny options'),
      '#open'         => $form_in_use,
    ];

    $form['addtoany_options']['link_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('The URL to share.'),
      '#default_value' => $this->configuration['link_url'],
      '#attributes' => $attributes_for_code,
    ];

    $form['addtoany_options']['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title to share.'),
      '#default_value' => $this->configuration['link_title'],
      '#attributes' => $attributes_for_code,
    ];

    $form['addtoany_options']['buttons_size'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Icon size'),
      '#field_suffix'  => ' ' . $this->t('pixels'),
      '#default_value' => $this->configuration['buttons_size'],
      '#size'          => 10,
      '#maxlength'     => 3,
      '#min'           => 8,
      '#max'           => 999,
      '#placeholder'   => ['32'],
    ];

    $form['addtoany_options']['addtoany_html'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Service Buttons HTML code'),
      '#default_value' => $this->configuration['addtoany_html'],
      '#description'   => $this->t('You can add HTML code to display customized <a href="https://www.addtoany.com/buttons/customize/drupal/standalone_services" target="_blank">standalone service buttons</a> next to each universal share button. For example: <br /> <code>&lt;a class=&quot;a2a_button_facebook&quot;&gt;&lt;/a&gt;<br />&lt;a class=&quot;a2a_button_twitter&quot;&gt;&lt;/a&gt;<br />&lt;a class=&quot;a2a_button_pinterest&quot;&gt;&lt;/a&gt;</code>
      '),
      '#attributes' => $attributes_for_code,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $options = $form_state->getValue('addtoany_options');

    foreach ($this->addtoany_option_keys as $key) {
      $this->configuration[$key] = $options[$key];
    }
  }

}
