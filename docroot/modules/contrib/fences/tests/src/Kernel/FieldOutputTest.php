<?php

namespace Drupal\Tests\fences\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\fences\Traits\StripWhitespaceTrait;

/**
 * Test the field output under different configurations.
 *
 * @group fences
 */
class FieldOutputTest extends KernelTestBase {

  use StripWhitespaceTrait;

  /**
   * The test field name.
   *
   * @var string
   */
  protected $fieldName = 'field_test';

  /**
   * The test field name.
   *
   * @var string
   */
  protected $fieldNameMultiple = 'field_test_multiple';

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_test';

  /**
   * The test entity used for testing output.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entity;

  /**
   * The entity display under test.
   *
   * @var \Drupal\Core\Entity\Entity\EntityViewDisplay
   */
  protected $entityViewDisplay;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'text',
    'filter',
    'entity_test',
    'field_test',
    'fences',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->installEntitySchema($this->entityTypeId);
    $this->installEntitySchema('filter_format');

    // Setup a field and an entity display.
    EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ])->save();
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityTypeId,
      'type' => 'text',
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityTypeId,
      'field_name' => $this->fieldName,
      'bundle' => $this->entityTypeId,
      'label' => 'Field Test',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => $this->fieldNameMultiple,
      'entity_type' => $this->entityTypeId,
      'type' => 'text',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityTypeId,
      'field_name' => $this->fieldNameMultiple,
      'bundle' => $this->entityTypeId,
      'label' => 'Field Test Multiple',
      'translatable' => FALSE,
    ])->save();

    $this->entityViewDisplay = EntityViewDisplay::load('entity_test.entity_test.default');

    // Create a test entity with a test value.
    $this->entity = EntityTest::create();
    $this->entity->set($this->fieldName, 'lorem ipsum');
    $this->entity->set($this->fieldNameMultiple, [
      'test value 1',
      'test value 2',
      'test value 3',
    ]);
    $this->entity->save();

    // Set the default filter format.
    FilterFormat::create([
      'format' => 'test_format',
      'name' => $this->randomMachineName(),
    ])->save();
    $this->container->get('config.factory')
      ->getEditable('filter.settings')
      ->set('fallback_format', 'test_format')
      ->save();
  }

  /**
   * Test cases for the field output test.
   */
  public function fieldTestCases() {
    return [
      'No field markup, no label' => [
        [
          'fences_field_tag' => 'none',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'none',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        FALSE,
        'lorem ipsum',
      ],
      'Only a field tag, no label' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'none',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        FALSE,
        '<article class="field field--name-field-test field--type-text field--label-hidden field__items">lorem ipsum</article>',
      ],
      'No field markup, with label' => [
        [
          'fences_field_tag' => 'none',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'none',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        TRUE,
        'Field Testlorem ipsum',
      ],
      'Only a field tag, with label' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'none',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        TRUE,
        '<article class="field field--name-field-test field--type-text field--label-above field__items">Field Testlorem ipsum</article>',
      ],
      'Only a field and label tag, with label' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'none',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'h3',
          'fences_label_classes' => '',
        ],
        TRUE,
        '<article class="field field--name-field-test field--type-text field--label-above field__items"><h3 class="field__label">Field Test</h3>lorem ipsum</article>',
      ],
      'Classes and tags, with label' => [
      [
        'fences_field_tag' => 'ul',
        'fences_field_classes' => 'item-list',
        'fences_field_items_wrapper_tag' => 'none',
        'fences_field_items_wrapper_classes' => '',
        'fences_field_item_tag' => 'li',
        'fences_field_item_classes' => 'item-list__item',
        'fences_label_tag' => 'li',
        'fences_label_classes' => 'item-list__label',
      ],
        TRUE,
        '<ul class="item-list field field--name-field-test field--type-text field--label-above field__items"><li class="item-list__label field__label">Field Test</li><li class="item-list__item field__item">lorem ipsum</li></ul>',
      ],
      'Only a field and field item tag, with label' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'none',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'h2',
          'fences_field_item_classes' => '',
          'fences_label_tag' => '',
          'fences_label_classes' => '',
        ],
        TRUE,
        '<article class="field field--name-field-test field--type-text field--label-above field__items"><div class="field__label">Field Test</div><h2 class="field__item">lorem ipsum</h2></article>',
      ],
      'Default field, with label' => [
        [
          'fences_field_tag' => '',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'none',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => '',
          'fences_field_item_classes' => '',
          'fences_label_tag' => '',
          'fences_label_classes' => '',
        ],
        TRUE,
        '<div class="field field--name-field-test field--type-text field--label-above field__items"><div class="field__label">Field Test</div><div class="field__item">lorem ipsum</div></div>',
      ],
      'Classes and tags, with label' => [
        [
          'fences_field_tag' => '',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'none',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => '',
          'fences_field_item_classes' => '',
          'fences_label_tag' => '',
          'fences_label_classes' => '',
        ],
        FALSE,
        '<div class="field field--name-field-test field--type-text field--label-hidden field__items"><div class="field__item">lorem ipsum</div></div>',
      ],
      'No field markup, no label, items wrapper only' => [
        [
          'fences_field_tag' => 'none',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'article',
          'fences_field_items_wrapper_classes' => 'items-wrapper',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        FALSE,
        '<article class="items-wrapper field__items">lorem ipsum</article>',
      ],
      'Field tag, items wrapper, no label' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'div',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        FALSE,
        '<article class="field field--name-field-test field--type-text field--label-hidden"><div class="field__items">lorem ipsum</div></article>',
      ],
      'No field markup, with label and items wrapper' => [
        [
          'fences_field_tag' => 'none',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'div',
          'fences_field_items_wrapper_classes' => 'items-wrapper',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        TRUE,
        'Field Test<div class="items-wrapper field__items">lorem ipsum</div>',
      ],
      'field tag, with label and items wrapper' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'div',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        TRUE,
        '<article class="field field--name-field-test field--type-text field--label-above">Field Test<div class="field__items">lorem ipsum</div></article>',
      ],
      'Field and label tag, with label and items wrapper' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'div',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'h3',
          'fences_label_classes' => '',
        ],
        TRUE,
        '<article class="field field--name-field-test field--type-text field--label-above"><h3 class="field__label">Field Test</h3><div class="field__items">lorem ipsum</div></article>',
      ],
      'Default field, default items wrapper, no label' => [
        [
          'fences_field_tag' => '',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => '',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => '',
          'fences_field_item_classes' => '',
          'fences_label_tag' => '',
          'fences_label_classes' => '',
        ],
        FALSE,
        '<div class="field field--name-field-test field--type-text field--label-hidden field__items"><div class="field__item">lorem ipsum</div></div>',
      ],
      'Field, Items wrapper and label, all classes set' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => 'tag-class',
          'fences_field_items_wrapper_tag' => 'div',
          'fences_field_items_wrapper_classes' => 'items-wrapper',
          'fences_field_item_tag' => 'div',
          'fences_field_item_classes' => 'item-wrapper',
          'fences_label_tag' => 'h2',
          'fences_label_classes' => 'label-class',
        ],
        FALSE,
        '<article class="tag-class field field--name-field-test field--type-text field--label-hidden"><div class="items-wrapper field__items"><div class="item-wrapper field__item">lorem ipsum</div></div></article>',
      ],
    ];
  }

  /**
   * Test Cases for fields with multiple items.
   */
  public function fieldTestMultipleCases() {
    return [
      'No field markup, no label, items wrapper only' => [
        [
          'fences_field_tag' => 'none',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'article',
          'fences_field_items_wrapper_classes' => 'items-wrapper',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        FALSE,
        '<article class="items-wrapper field__items">test value 1test value 2test value 3</article>',
      ],
      'Field tag, field item tag, items wrapper, no label' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'div',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'div',
          'fences_field_item_classes' => 'item-class',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        FALSE,
        '<article class="field field--name-field-test-multiple field--type-text field--label-hidden"><div class="field__items"><div class="item-class field__item">test value 1</div><div class="item-class field__item">test value 2</div><div class="item-class field__item">test value 3</div></div></article>',
      ],
      'Field tag, field item tag, items wrapper, with label and label tag' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'div',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'div',
          'fences_field_item_classes' => 'item-class',
          'fences_label_tag' => 'h2',
          'fences_label_classes' => 'label-class',
        ],
        TRUE,
        '<article class="field field--name-field-test-multiple field--type-text field--label-above"><h2 class="label-class field__label">Field Test Multiple</h2><div class="field__items"><div class="item-class field__item">test value 1</div><div class="item-class field__item">test value 2</div><div class="item-class field__item">test value 3</div></div></article>',
      ],
      'No field markup, with label and items wrapper' => [
        [
          'fences_field_tag' => 'div',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'ul',
          'fences_field_items_wrapper_classes' => 'items-wrapper',
          'fences_field_item_tag' => 'li',
          'fences_field_item_classes' => 'item-class',
          'fences_label_tag' => 'h2',
          'fences_label_classes' => '',
        ],
        TRUE,
        '<div class="field field--name-field-test-multiple field--type-text field--label-above"><h2 class="field__label">Field Test Multiple</h2><ul class="items-wrapper field__items"><li class="item-class field__item">test value 1</li><li class="item-class field__item">test value 2</li><li class="item-class field__item">test value 3</li></ul></div>',
      ],
      'field tag, with label and items wrapper' => [
        [
          'fences_field_tag' => 'article',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'div',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'none',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        TRUE,
        '<article class="field field--name-field-test-multiple field--type-text field--label-above">Field Test Multiple<div class="field__items">test value 1test value 2test value 3</div></article>',
      ],
      'No field markup, no label, item tag only' => [
        [
          'fences_field_tag' => 'none',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'none',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'div',
          'fences_field_item_classes' => 'item-wrapper',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        FALSE,
        '<div class="item-wrapper field__item">test value 1</div><div class="item-wrapper field__item">test value 2</div><div class="item-wrapper field__item">test value 3</div>',
      ],
      'No field markup, no label, item tag only with label' => [
        [
          'fences_field_tag' => 'none',
          'fences_field_classes' => '',
          'fences_field_items_wrapper_tag' => 'none',
          'fences_field_items_wrapper_classes' => '',
          'fences_field_item_tag' => 'div',
          'fences_field_item_classes' => '',
          'fences_label_tag' => 'none',
          'fences_label_classes' => '',
        ],
        TRUE,
        'Field Test Multiple<div class="field__item">test value 1</div><div class="field__item">test value 2</div><div class="field__item">test value 3</div>',
      ],
    ];
  }

  /**
   * Test the field output.
   *
   * @dataProvider fieldTestCases
   */
  public function testFieldOutput($settings, $label_visible, $field_markup) {
    // The entity display must be updated because the view method on fields
    // doesn't support passing third party settings.
    $this->entityViewDisplay->setComponent($this->fieldName, [
      'label' => $label_visible ? 'above' : 'hidden',
      'settings' => [],
      'type' => 'text_default',
      'third_party_settings' => [
        'fences' => $settings,
      ],
    ])->setStatus(TRUE)->save();
    $field_output = $this->entity->{$this->fieldName}->view('default');
    $rendered_field_output = $this->stripWhitespace($this->container->get('renderer')
      ->renderRoot($field_output));
    $this->assertEquals($this->stripWhitespace($field_markup), $rendered_field_output);
  }

  /**
   * Test the field output for a field with multiple items.
   *
   * @dataProvider fieldTestMultipleCases
   */
  public function testFieldOutputMultiple($settings, $label_visible, $field_markup) {
    // The entity display must be updated because the view method on fields
    // doesn't support passing third party settings.
    $this->entityViewDisplay->setComponent($this->fieldNameMultiple, [
      'label' => $label_visible ? 'above' : 'hidden',
      'settings' => [],
      'type' => 'text_default',
      'third_party_settings' => [
        'fences' => $settings,
      ],
    ])->setStatus(TRUE)->save();
    $field_output = $this->entity->{$this->fieldNameMultiple}->view('default');
    $rendered_field_output = $this->stripWhitespace($this->container->get('renderer')
      ->renderRoot($field_output));
    $this->assertEquals($this->stripWhitespace($field_markup), $rendered_field_output);
  }

}
