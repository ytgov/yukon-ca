<?php

namespace Drupal\Tests\cshs\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the CSHS module.
 *
 * @group cshs
 */
class CshsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable9';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cshs',
    'cshs_test',
    'taxonomy',
    'user',
    'node',
    'views',
  ];

  /**
   * Test views integration.
   */
  public function testCshsViews(): void {
    $user = $this->drupalCreateUser([
      'access content',
    ]);
    $ct = $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);
    // Create a node to display by the view.
    $this->drupalCreateNode([
      'uid' => $user->id(),
      'type' => $ct->id(),
      'status' => NodeInterface::PUBLISHED,
    ]);

    $this->drupalLogin($user);

    $this->drupalGet('cshs');
    $assert = $this->assertSession();

    $assert->statusCodeEquals(200);
    $assert->pageTextContains('CSHS view');

    $assert->elementExists('css', '#edit-tid');
    $assert->pageTextContains('Term ID');

    $assert->elementExists('css', '#edit-tid-depth');
    $assert->pageTextContains('Term ID (Depth)');
  }

}
