<?php

namespace Drupal\Tests\single_content_sync\Functional;

/**
 * Tests different import functionalities through the UI.
 *
 * @group single_content_sync
 */
class SingleContentSyncImportUITest extends SingleContentSyncBrowserTestBase {

  /**
   * Test import of a very simple basic page through the UI.
   */
  public function testSingleImportUi() {
    // First check if there's no content.
    $this->drupalGet('admin/content');
    $this->assertSession()->pageTextContains('There are no content items yet.');

    // We go to the form page to import.
    $this->drupalGet('/admin/content/import');
    $edit = [];
    $edit['edit-content'] = "uuid: ccc0f424-2db5-42f5-9f73-1ed511e8aa71
entity_type: node
bundle: page
base_fields:
  title: Llama
  status: true
  langcode: en
  created: '1647123536'
  changed: '1647123543'
  author: admin@example.com
  url: null
custom_fields:
  body: null";
    $this->submitForm($edit, 'Import');

    // We check if the node is created.
    $this->drupalGet('admin/content');
    $this->assertSession()->pageTextContains('Llama');
  }

}
