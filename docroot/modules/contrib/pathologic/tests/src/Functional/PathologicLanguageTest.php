<?php

namespace Drupal\Tests\pathologic\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test multilingual integration of Pathologic functionality.
 *
 * @group filter
 */
class PathologicLanguageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'locale',
    'pathologic',
    'node',
  ];

  /**
   * A user with permissions to administer content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page'
    ]);

    $permissions = [
      'access administration pages',
      'administer filters',
      'administer languages',
      'administer content translation',
      'administer content types',
      'administer languages',
      'create content translations',
      'create page content',
      'edit any page content',
      'translate any entity',
    ];
    $this->webUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->webUser);

    // Add a second language.
    $edit = [];
    $edit['predefined_langcode'] = 'fr';
    $this->drupalGet('/admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => 1];
    $this->drupalGet('/admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Enable translation for page node.
    $edit = [
      'entity_types[node]' => 1,
      'settings[node][page][translatable]' => 1,
      'settings[node][page][fields][body]' => 1,
      'settings[node][page][settings][language][language_alterable]' => 1,
    ];
    $this->drupalGet('/admin/config/regional/content-language');
    $this->submitForm($edit, 'Save configuration');

    // Configure Pathologic on a text format.
    $this->drupalGet('/admin/config/content/formats/manage/plain_text');
    $this->submitForm([
      'filters[filter_html_escape][status]' => FALSE,
      'filters[filter_pathologic][status]' => '1',
      'filters[filter_pathologic][settings][settings_source]' => 'local',
      'filters[filter_pathologic][settings][local_settings][protocol_style]' => 'path',
    ], t('Save configuration'));
  }

  /**
   * Pathologic should not prefix URLs in content translations.
   */
  public function testContentTranslation() {
    $node_to_reference = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Reference node',
    ]);
    // Create a default-language node with a node link and a file link.
    $default_language_node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Lost in translation',
      'body' => [
        'value' => '<a href="/node/' . $node_to_reference->id() . '">Test node link</a><a href="/sites/default/files/test.png">Test file link</a>',
        'format' => 'plain_text',
      ],
    ]);
    $this->drupalGet('/node/' . $default_language_node->id());
    // Links on the default language node should not contain a language prefix.
    $this->assertSession()->linkByHrefExists('/sites/default/files/test.png');
    $this->assertSession()->linkByHrefNotExists('/en/sites/default/files/test.png');
    $this->assertSession()->linkByHrefExists('/node/' . $node_to_reference->id());
    $this->assertSession()->linkByHrefNotExists('/en/node/' . $node_to_reference->id());

    // Create a translation of the node, same content.
    $this->drupalGet('/node/' . $default_language_node->id() . '/translations/add/en/fr');
    $this->submitForm([], 'Save');

    // Links on the translation should *not* contain a language prefix.
    $this->drupalGet('/fr/node/' . $default_language_node->id());
    $this->assertSession()->linkByHrefExists('/node/' . $node_to_reference->id());
    $this->assertSession()->linkByHrefNotExists('/fr/node/' . $node_to_reference->id());
    $this->assertSession()->linkByHrefExists('/sites/default/files/test.png');
    $this->assertSession()->linkByHrefNotExists('/fr/sites/default/files/test.png');
  }

}
