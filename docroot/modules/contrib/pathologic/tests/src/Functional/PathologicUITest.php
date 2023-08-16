<?php

namespace Drupal\Tests\pathologic\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the Pathologic UI.
 *
 * @group pathologic
 */
class PathologicUITest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['pathologic', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalLogin($this->drupalCreateUser(['administer filters', 'create page content']));
  }

  /**
   * Tests for the Pathologic UI.
   */
  public function testPathologicUi() {
    $this->doTestSettingsForm();
    $this->doTestFormatsOptions();
    $this->doTestFixUrl();
  }

  /**
   * Test settings form.
   */
  public function doTestSettingsForm() {
    $this->drupalGet('admin/config/content/pathologic');
    $this->assertSession()->pageTextContains('Pathologic configuration');

    // Test submit form.
    $this->assertSession()->checkboxNotChecked('edit-protocol-style-proto-rel');
    $edit = [
      'protocol_style' => 'proto-rel',
      'local_paths' => 'http://example.com/',
    ];
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->checkboxChecked('edit-protocol-style-proto-rel');
    $this->assertSession()->pageTextContains('http://example.com/');
    $docs_link = $this->getSession()->getPage()->findLink('Pathologic’s documentation');
    $this->assertNotEmpty($docs_link);
    $this->assertSame('https://www.drupal.org/node/257026', $docs_link->getAttribute('href'));
  }

  /**
   * Test text formats and editors options with pathologic.
   */
  public function doTestFormatsOptions() {

    // Test plain text with pathologic configuration.
    $this->drupalGet('/admin/config/content/formats/manage/plain_text');

    // Select pathologic option.
    $this->assertSession()->pageTextContains('Correct URLs with Pathologic');
    $this->assertSession()->checkboxNotChecked('edit-filters-filter-pathologic-status');
    $this->submitForm([
      'filters[filter_html_escape][status]' => FALSE,
      'filters[filter_pathologic][status]' => '1',
    ], t('Save configuration'));

    $this->drupalGet('/admin/config/content/formats/manage/plain_text');
    $this->assertSession()->responseContains('In most cases, Pathologic should be the <em>last</em> filter in the &ldquo;Filter processing order&rdquo; list.');
    $this->assertSession()->pageTextContains('Select whether Pathologic should use the global Pathologic settings');
    $this->assertSession()->checkboxChecked('edit-filters-filter-pathologic-status');
    $this->submitForm([
      'filters[filter_pathologic][settings][settings_source]' => 'local',
      'filters[filter_pathologic][settings][local_settings][protocol_style]' => 'full',
      ], t('Save configuration'));

    $this->drupalGet('/admin/config/content/formats/manage/plain_text');
    $this->assertSession()->checkboxChecked('edit-filters-filter-pathologic-settings-settings-source-local');
    $this->assertSession()->checkboxChecked('edit-filters-filter-pathologic-settings-local-settings-protocol-style-full');
    $this->assertSession()->pageTextContains('Custom settings for this text format');
  }

  /**
   * Test that a url is fixed with pathologic.
   */
  public function doTestFixUrl() {
    $this->drupalGet('node/add/page');
    $edit = [
      'title[0][value]' => 'Test pathologic',
      'body[0][value]' => '<a href="node/1">Test link</a>',
    ];
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, t('Save'));

    // Assert that the link is processed with Pathologic.
    $this->clickLink('Test link');
    $this->assertSession()->titleEquals('Test pathologic | Drupal');
  }

}
