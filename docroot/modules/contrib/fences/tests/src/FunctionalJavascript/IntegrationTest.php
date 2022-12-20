<?php

namespace Drupal\Tests\fences\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * A fences integration test.
 *
 * @group fences
 */
class IntegrationTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'test_page_test',
    'node',
    'field',
    'field_ui',
    'fences',
  ];

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  public function setUp():void {
    parent::setUp();
    $this->config('system.site')->set('page.front', '/test-page')->save();

    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $this->node = $this->drupalCreateNode([
      'title' => $this->randomString(),
      'type' => 'article',
      'body' => 'Body field value.',
    ]);
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer node display',
      'edit fences formatter settings',
    ]);
    // User without "Edit fences formatter settings":
    $this->user = $this->drupalCreateUser([
      'access content',
      'administer node display',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the basic settings.
   */
  public function testBasicSettings() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/structure/types/manage/article/display');
    $page->pressButton('edit-fields-body-settings-edit');

    $session->waitForElementVisible('css', 'div[id*="edit-fields-body-settings-edit-form"]');
    $this->submitForm([
      'fields[body][label]' => 'above',
      'fields[body][settings_edit_form][third_party_settings][fences][fences_field_tag]' => 'article',
      'fields[body][settings_edit_form][third_party_settings][fences][fences_field_classes]' => 'my-field-class',
      'fields[body][settings_edit_form][third_party_settings][fences][fences_field_item_tag]' => 'code',
      'fields[body][settings_edit_form][third_party_settings][fences][fences_field_item_classes]' => 'my-field-item-class',
      'fields[body][settings_edit_form][third_party_settings][fences][fences_label_tag]' => 'h2',
      'fields[body][settings_edit_form][third_party_settings][fences][fences_label_classes]' => 'my-label-class',
    ], 'Update');
    $session->waitForElementRemoved('css', 'div[id*="edit-fields-body-settings-edit-form"]');
    $page->pressButton('edit-submit');

    $page = $this->drupalGet('/node/' . $this->node->id());
    $article = $session->elementExists('css', '.field--name-body');
    $this->assertTrue($article->hasClass('my-field-class'), 'Custom field class is present.');
    $label = $session->elementExists('css', 'h2.my-label-class', $article);
    $this->assertSame($label->getText(), 'Body', 'Field label is found in expected HTML element.');
    $body = $session->elementExists('css', 'code.my-field-item-class > p', $article);
    $this->assertSame($body->getText(), 'Body field value.', 'Field text is found in expected HTML element.');
  }

  /**
   * Tests the "edit fences formatter settings" permission.
   */
  public function testEditFencesFormatterSettingsPermission() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Go to display page and see if the fences settings are there:
    $this->drupalGet('/admin/structure/types/manage/article/display');
    $page->pressButton('edit-fields-body-settings-edit');
    $session->waitForElementVisible('css', 'div[id*="edit-fields-body-settings-edit-form"]');
    $session->elementExists('css', 'fieldset[id*="edit-fields-body-settings-edit-form-third-party-settings-fences"]');
    $this->drupalLogout();
    // Login with a user without the 'edit fences formatter settings'
    // permission and see if the settings are NOT displayed anymore:
    $this->drupalLogin($this->user);
    $this->drupalGet('/admin/structure/types/manage/article/display');
    $page->pressButton('edit-fields-body-settings-edit');
    $session->waitForElementVisible('css', 'div[id*="edit-fields-body-settings-edit-form"]');
    $session->elementNotExists('css', 'fieldset[id*="edit-fields-body-settings-edit-form-third-party-settings-fences"]');
  }

}
