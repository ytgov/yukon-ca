<?php

namespace Drupal\Tests\single_content_sync\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Browser test base class for Single content sync functional tests.
 *
 * @group single_content_sync
 */
abstract class SingleContentSyncBrowserTestBase extends BrowserTestBase {

  use SingleContentSyncImportContentTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['single_content_sync', 'node', 'path'];

  /**
   * An user with permissions to view and create content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->createContentType([
        'type' => 'page',
        'name' => 'Basic page',
      ]);
      $this->createContentType([
        'type' => 'article',
        'name' => 'Article',
      ]);
    }

    $this->adminUser = $this->createUser([
      'access content',
      'access administration pages',
      'create page content',
      'edit any page content',
      'delete any page content',
      'create article content',
      'edit any article content',
      'delete any article content',
      'access content overview',
      'import single content',
      'export single content',
      'export node content',
    ]);
    $this->drupalLogin($this->adminUser);

  }

}
