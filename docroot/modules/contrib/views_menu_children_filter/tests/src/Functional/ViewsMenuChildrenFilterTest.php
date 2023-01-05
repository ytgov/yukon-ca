<?php

namespace Drupal\Tests\views_menu_children_filter\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * This class provides methods specifically for testing something.
 *
 * @group views_menu_children_filter
 */
class ViewsMenuChildrenFilterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'menu_link_content',
    'block',
    'block_content',
    'views',
    'test_page_test',
    'views_menu_children_filter',
    'views_menu_children_filter_test',
  ];

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->user = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if installing the module, won't break the site.
   */
  public function testInstallation() {
    $session = $this->assertSession();
    $this->drupalGet('<front>');
    // Ensure the status code is success:
    $session->statusCodeEquals(200);
    // Ensure the correct test page is loaded as front page:
    $session->pageTextContains('Test page text.');
  }

  /**
   * Tests if uninstalling the module, won't break the site.
   */
  public function testUninstallation() {
    // Go to uninstallation page an uninstall views_menu_children_filter:
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/modules/uninstall');
    $session->statusCodeEquals(200);
    $page->checkField('edit-uninstall-views-menu-children-filter-test');
    $page->checkField('edit-uninstall-views-menu-children-filter');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    // Confirm uninstall:
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The selected modules have been uninstalled.');
    // Retest the frontpage:
    $this->drupalGet('<front>');
    // Ensure the status code is success:
    $session->statusCodeEquals(200);
    // Ensure the correct test page is loaded as front page:
    $session->pageTextContains('Test page text.');
  }

  /**
   * Creates nodes with menu links to the main navigation.
   */
  public function createNodesWithMenuLinks() {
    $this->createContentType(['type' => 'article']);
    $node1 = $this->createNode(['type' => 'article', 'title' => 'Node 1']);
    $node2 = $this->createNode(['type' => 'article', 'title' => 'Node 2']);
    $node3 = $this->createNode(['type' => 'article', 'title' => 'Node 3']);
    $node4 = $this->createNode(['type' => 'article', 'title' => 'Node 4']);
    $menu_link1 = MenuLinkContent::create([
      'title' => 'Node 1',
      'provider' => 'menu_link_content',
      'menu_name' => 'main',
      'link' => ['uri' => 'entity:node/' . $node1->id()],
    ]);
    $menu_link1->save();
    MenuLinkContent::create([
      'title' => 'Node 2',
      'provider' => 'menu_link_content',
      'menu_name' => 'main',
      'parent' => $menu_link1->getPluginId(),
      'weight' => 1,
      'link' => ['uri' => 'entity:node/' . $node2->id()],
    ])->save();
    MenuLinkContent::create([
      'title' => 'Node 3',
      'provider' => 'menu_link_content',
      'menu_name' => 'main',
      'parent' => $menu_link1->getPluginId(),
      'weight' => 2,
      'link' => ['uri' => 'entity:node/' . $node3->id()],
    ])->save();
    MenuLinkContent::create([
      'title' => 'Node 4',
      'provider' => 'menu_link_content',
      'menu_name' => 'main',
      'parent' => $menu_link1->getPluginId(),
      'weight' => 0,
      'link' => ['uri' => 'entity:node/' . $node4->id()],
    ])->save();
  }

  /**
   * Test the argument view as a block.
   */
  public function testArgumentViewAsBlock() {
    $session = $this->assertSession();
    $this->createNodesWithMenuLinks();
    $this->placeBlock('views_block:views_menu_children_filter_argument_test-block_1', [
      'id' => 'test_block',
      'context_mapping' => ['menu_children_filter' => '@node.node_route_context:node']
    ]);
    $this->drupalGet('/node/1');
    $session->statusCodeEquals(200);
    // Check if the block shows the children nodes in the correct order not
    // depending on weight:
    $session->elementTextEquals('css', '#block-test-block > div > div > div:nth-child(1).views-row > div.views-field > span.field-content > a', 'Node 2');
    $session->elementTextEquals('css', '#block-test-block > div > div > div:nth-child(2).views-row > div.views-field > span.field-content > a', 'Node 3');
    $session->elementTextEquals('css', '#block-test-block > div > div > div:nth-child(3).views-row > div.views-field > span.field-content > a', 'Node 4');
  }

  /**
   * Test the argument view as a block.
   */
  public function testArgumentWithWeightSortViewAsBlock() {
    $session = $this->assertSession();
    $this->createNodesWithMenuLinks();
    $this->placeBlock('views_block:argument_with_sort_test-block_1', [
      'id' => 'test_block',
      'context_mapping' => ['menu_children_filter' => '@node.node_route_context:node']
    ]);
    $this->drupalGet('/node/1');
    $session->statusCodeEquals(200);
    // Check if the block shows the children nodes in the correct order,
    // depending on weight.
    $session->elementTextEquals('css', '#block-test-block > div > div > div:nth-child(1).views-row > div.views-field > span.field-content > a', 'Node 4');
    $session->elementTextEquals('css', '#block-test-block > div > div > div:nth-child(2).views-row > div.views-field > span.field-content > a', 'Node 2');
    $session->elementTextEquals('css', '#block-test-block > div > div > div:nth-child(3).views-row > div.views-field > span.field-content > a', 'Node 3');
  }

  /**
   * Test if after setup it is still possible to create an article via UI.
   *
   * Test for mitigating reappearance of
   * https://www.drupal.org/project/views_menu_children_filter/issues/3315919
   */
  public function testCreationOfNodeAfterSetup() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->createNodesWithMenuLinks();
    $this->placeBlock('views_block:views_menu_children_filter_argument_test-block_1', [
      'id' => 'test_block',
      'context_mapping' => ['menu_children_filter' => '@node.node_route_context:node']
    ]);
    $this->drupalGet('/node/1');
    $session->statusCodeEquals(200);
    // Check if the block shows the children nodes in the correct order not
    // depending on weight:
    $session->elementTextEquals('css', '#block-test-block > div > div > div:nth-child(1).views-row > div.views-field > span.field-content > a', 'Node 2');
    $session->elementTextEquals('css', '#block-test-block > div > div > div:nth-child(2).views-row > div.views-field > span.field-content > a', 'Node 3');
    $session->elementTextEquals('css', '#block-test-block > div > div > div:nth-child(3).views-row > div.views-field > span.field-content > a', 'Node 4');
    // Create an article via ui:
    $this->drupalGet('/node/add/article');
    $page->fillField('edit-title-0-value', 'Specific Test Node');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Article Specific Test Node has been created.');
    // Go to the created article and check if no children are shown:
    $this->drupalGet('/node/5');
    $session->statusCodeEquals(200);
    // Check if it is the correct page:
    $session->pageTextContains('Specific Test Node');
    // Check if the block shows the children nodes in the correct order not
    // depending on weight:
    $session->elementNotExists('css', '#block-test-block > div > div > div:nth-child(1).views-row > div.views-field > span.field-content > a');
    $session->elementNotExists('css', '#block-test-block > div > div > div:nth-child(2).views-row > div.views-field > span.field-content > a');
    $session->elementNotExists('css', '#block-test-block > div > div > div:nth-child(3).views-row > div.views-field > span.field-content > a');
  }

}
