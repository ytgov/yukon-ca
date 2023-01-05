<?php

namespace Drupal\Tests\workbench_email\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\UserInterface;
use Drupal\workbench_email\Entity\Template;
use Drupal\workflows\Entity\Workflow;

/**
 * Defines a class for testing workbench email with content moderation.
 *
 * @group workbench_email
 */
final class WorkbenchEmailTest extends BrowserTestBase {

  use AssertMailTrait;
  use NodeCreationTrait;
  use BlockCreationTrait;

  const ADMIN_PERMISSIONS = [
    'administer workflows',
    'administer workbench_email templates',
    'access administration pages',
  ];

  const EDITOR_PERMISSIONS = [
    'view any unpublished content',
    'access content',
    'edit any test content',
    'create test content',
    'view test revisions',
    'edit any another content',
    'create another content',
    'view another revisions',
    'use editorial transition draft_needs_review',
    'use editorial transition create_new_draft',
    'view latest version',
  ];

  const APPROVER_PERMISSIONS = [
    'view any unpublished content',
    'access content',
    'edit any test content',
    'create test content',
    'view test revisions',
    'edit any another content',
    'create another content',
    'view another revisions',
    'use editorial transition draft_needs_review',
    'use editorial transition needs_review_published',
    'view latest version',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test node type.
   */
  protected NodeTypeInterface $nodeType;

  /**
   * Approver 1.
   */
  protected UserInterface $approver1;

  /**
   * Approver 2.
   */
  protected UserInterface $approver2;

  /**
   * Approver 3 - blocked.
   */
  protected UserInterface $approver3;

  /**
   * Approver 4 - no email address.
   */
  protected UserInterface $approver4;

  /**
   * Editor.
   */
  protected UserInterface $editor;

  /**
   * Admin.
   */
  protected UserInterface $admin;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workbench_email',
    'workbench_email_test',
    'node',
    'options',
    'user',
    'system',
    'filter',
    'block',
    'field',
    'content_moderation',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    // Place some blocks.
    $this->placeBlock('local_tasks_block', ['id' => 'tabs_block']);
    $this->placeBlock('page_title_block');
    $this->placeBlock('local_actions_block', ['id' => 'actions_block']);
    // Create two node-types and make them moderated.
    $this->nodeType = NodeType::create([
      'type' => 'test',
      'name' => 'Test',
    ]);
    $this->setupModerationForNodeType($this->nodeType);
    $this->nodeType = NodeType::create([
      'type' => 'another',
      'name' => 'Another Test',
    ]);
    $this->setupModerationForNodeType($this->nodeType);
    // Create an approver role and two users.
    $this->drupalCreateRole(self::APPROVER_PERMISSIONS, 'approver', 'Approver');
    $this->approver1 = $this->drupalCreateUser();
    $this->approver1->addRole('approver');
    $this->approver1->save();
    $this->approver2 = $this->drupalCreateUser();
    $this->approver2->addRole('approver');
    $this->approver2->save();
    $this->approver3 = $this->drupalCreateUser();
    $this->approver3->addRole('approver');
    $this->approver3->block();
    $this->approver3->save();
    $this->approver4 = $this->drupalCreateUser();
    $this->approver4->addRole('approver');
    $this->approver4->setEmail(NULL);
    $this->approver4->save();

    // Create a editor role and user.
    $this->drupalCreateRole(self::EDITOR_PERMISSIONS, 'editor', 'Editor');
    $this->editor = $this->drupalCreateUser();
    $this->editor->addRole('editor');
    $this->editor->save();
    // Create an admin user.
    $this->admin = $this->drupalCreateUser(self::ADMIN_PERMISSIONS);
    // Add an email field notify to the node-type.
    FieldStorageConfig::create([
      'cardinality' => 1,
      'entity_type' => 'node',
      'field_name' => 'field_email',
      'type' => 'email',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_email',
      'bundle' => 'test',
      'label' => 'Notify',
      'entity_type' => 'node',
    ])->save();
    if (!$entity_form_display = EntityFormDisplay::load('node.test.default')) {
      $entity_form_display = EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => 'test',
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $entity_form_display->setComponent('field_email', ['type' => 'email_default'])
      ->save();
  }

  /**
   * Enables moderation for a given node type.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   Node type to enable moderation for.
   */
  protected function setupModerationForNodeType(NodeTypeInterface $node_type) {
    $node_type->save();
    $typeSettings = [
      'states' => [
        'archived' => [
          'label' => 'Archived',
          'weight' => 5,
          'published' => FALSE,
          'default_revision' => TRUE,
        ],
        'draft' => [
          'label' => 'Draft',
          'published' => FALSE,
          'default_revision' => FALSE,
          'weight' => -5,
        ],
        'needs_review' => [
          'label' => 'Needs review',
          'published' => FALSE,
          'default_revision' => FALSE,
          'weight' => -4,
        ],
        'published' => [
          'label' => 'Published',
          'published' => TRUE,
          'default_revision' => TRUE,
          'weight' => 0,
        ],
      ],
      'transitions' => [
        'archive' => [
          'label' => 'Archive',
          'from' => ['published'],
          'to' => 'archived',
          'weight' => 2,
        ],
        'archived_draft' => [
          'label' => 'Restore to Draft',
          'from' => ['archived'],
          'to' => 'draft',
          'weight' => 3,
        ],
        'draft_needs_review' => [
          'label' => 'Request Review',
          'from' => ['draft'],
          'to' => 'needs_review',
          'weight' => 3,
        ],
        'archived_published' => [
          'label' => 'Restore',
          'from' => ['archived'],
          'to' => 'published',
          'weight' => 4,
        ],
        'create_new_draft' => [
          'label' => 'Create New Draft',
          'to' => 'draft',
          'weight' => 0,
          'from' => [
            'draft',
            'published',
            'needs_review',
          ],
        ],
        'needs_review_published' => [
          'label' => 'Publish',
          'to' => 'published',
          'weight' => 1,
          'from' => [
            'needs_review',
            'draft',
            'published',
          ],
        ],
      ],
    ];
    if (!($workflow = Workflow::load('editorial'))) {
      $workflow = Workflow::create([
        'type' => 'content_moderation',
        'id' => 'editorial',
        'label' => 'Editorial',
        'type_settings' => $typeSettings,
      ]);
    }
    else {
      if ($node_type->id() === 'test') {
        // Only do this the first time around.
        $workflow->getTypePlugin()->setConfiguration($typeSettings);
      }
    }
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', $node_type->id());
    $workflow->save();
  }

  /**
   * Enables template for given transition or workflow.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Transition or workflow.
   */
  protected function enableTemplateForTransitionOrWorkflow($transition_name, $template_name) {
    $this->drupalGet('admin/config/workflow/workbench-email-template/' . $template_name . '/edit');
    $field_name = 'transitions[editorial][' . $transition_name . ']';
    $this->submitForm([
      $field_name => $transition_name,
    ], t('Save'));
    $entityStorage = \Drupal::entityTypeManager()->getStorage('workbench_email_template');
    $entityStorage->resetCache();
    $template = $entityStorage->load($template_name);
    $this->drupalGet('admin/config/workflow/workbench-email-template/' . $template_name . '/edit');
    $this->assertSession()->checkboxChecked($field_name);
    return $template;
  }

  /**
   * Test administration.
   */
  public function testEndToEnd() {
    // Create some templates as admin.
    // - stuff got approved; and
    // - stuff needs review.
    $this->drupalLogin($this->admin);
    $this->drupalGet('admin/config/workflow');
    $page = $this->getSession()->getPage();
    $page->clickLink('Email Templates');
    $assert = $this->assertSession();
    $this->assertEquals($this->getSession()->getCurrentUrl(), Url::fromUri('internal:/admin/config/workflow/workbench-email-template')->setOption('absolute', TRUE)->toString());
    $assert->pageTextContains('Email Template');
    $page->clickLink('Add Email Template');
    $this->submitForm([
      'id' => 'approved',
      'label' => 'Content approved',
      'body[value]' => 'Content with [node:field_does_not_exist]title [node:title] was approved. You can view it at [node:url].',
      'subject' => 'Content [node:field_does_not_exist]approved: [node:title][node:field_does_not_exist]',
      'enabled_recipient_types[author]' => TRUE,
      'enabled_recipient_types[email]' => TRUE,
      'enabled_recipient_types[role]' => TRUE,
      'recipient_types[email][settings][fields][node:field_email]' => TRUE,
      'recipient_types[role][settings][roles][editor]' => TRUE,
    ], t('Save'));
    $assert->pageTextContains('Created the Content approved Email Template');
    $page->clickLink('Add Email Template');
    $this->submitForm([
      'id' => 'needs_review',
      'label' => 'Content needs review',
      'body[value]' => 'Content with [node:field_does_not_exist]title [node:title] needs review. You can view it at [node:url].[node:field_does_not_exist]',
      'subject' => 'Content needs review',
      'replyTo' => '[node:author:mail]',
      'enabled_recipient_types[role]' => TRUE,
      'recipient_types[role][settings][roles][approver]' => TRUE,
      'bundles[node:test]' => TRUE,
    ], t('Save'));
    $assert->pageTextContains('Created the Content needs review Email Template');
    // Test dependencies.
    $approver = Template::load('needs_review');
    $dependencies = $approver->calculateDependencies()->getDependencies()['config'];
    $this->assertTrue(in_array('user.role.approver', $dependencies, TRUE));
    $this->assertTrue(in_array('node.type.test', $dependencies, TRUE));
    $approver = Template::load('approved');
    $dependencies = $approver->calculateDependencies()->getDependencies()['config'];
    $this->assertTrue(in_array('field.storage.node.field_email', $dependencies, TRUE));
    // Edit the template and test values persisted.
    $page->clickLink('Content approved');
    $assert->checkboxChecked('Notify (Content)');
    $this->getSession()->back();
    // Test editing a template.
    $page->clickLink('Content needs review');
    $assert->checkboxChecked('Approver', $page->find('css', '#edit-recipient-types-role-settings-roles--wrapper'));
    $this->submitForm([
      'label' => 'Content needs review',
      'body[value]' => 'Content with[node:field_does_not_exist] title [node:title] needs review. You can view it at [node:url].[node:field_does_not_exist]',
      'subject' => 'Content needs[node:field_does_not_exist] review: [node:title][node:field_does_not_exist]',
      'replyTo' => '[node:author:mail]',
    ], t('Save'));
    $assert->pageTextContains('Saved the Content needs review Email Template');
    // Edit the transition from needs review to published and use the
    // needs_review email template.
    $this->enableTemplateForTransitionOrWorkflow('needs_review_published', 'approved');
    $template = Template::load('approved');
    $this->assertEquals(['editorial' => ['needs_review_published' => 'needs_review_published']], $template->getTransitions());

    // Edit the transition from draft to needs review and add email config:
    // approver template.
    $this->enableTemplateForTransitionOrWorkflow('draft_needs_review', 'needs_review');
    // Create a node and add to the notifier field.
    $this->drupalLogin($this->editor);
    $this->drupalGet('node/add/test');
    $this->submitForm([
      'title[0][value]' => 'Test node',
      'field_email[0][value]' => 'foo@example.com',
      'moderation_state[0][state]' => 'draft',
    ], 'Save');
    $node = $this->getNodeByTitle('Test node');
    // Transition to needs review.
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Reset collected email.
    $this->container->get('state')->set('system.test_mail_collector', []);
    $this->submitForm(['moderation_state[0][state]' => 'needs_review'], 'Save');
    $this->assertNeedsReviewNotifications($node);

    // Now try again going straight to needs review (no draft).
    // Reset collected email.
    $this->container->get('state')->set('system.test_mail_collector', []);
    // Create a node and add to the notifier field.
    $this->drupalGet('node/add/test');
    $this->submitForm([
      'title[0][value]' => 'Test node 2',
      'moderation_state[0][state]' => 'needs_review',
    ], 'Save');
    $node2 = $this->getNodeByTitle('Test node 2');
    $this->assertNeedsReviewNotifications($node2);

    // Login as approver and transition to approved.
    $this->container->get('state')->set('system.test_mail_collector', []);
    $this->drupalLogin($this->approver1);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm(['moderation_state[0][state]' => 'published'], 'Save');
    // Check mail goes to author and notifier.
    $captured_emails = $this->container->get('state')->get('system.test_mail_collector') ?: [];
    $last = end($captured_emails);
    $prev = prev($captured_emails);
    $mails = [$last['to'], $prev['to']];
    sort($mails);
    $expected = [$this->editor->getEmail(), 'foo@example.com'];
    sort($expected);
    $this->assertEquals($expected, $mails);

    // The node id text is added to the email subject in the
    // workbench_email_test_mail_alter() function.
    // We check that it is set here.
    $this->assertEquals(sprintf('Content approved: %s (node id: %s)', $node->getTitle(), $node->id()), $last['subject']);
    $this->assertEquals(sprintf('Content approved: %s (node id: %s)', $node->getTitle(), $node->id()), $prev['subject']);
    $this->assertStringContainsString(sprintf('Content with title %s was approved. You can view it at', $node->label()), preg_replace('/\s+/', ' ', $prev['body']));
    $this->assertStringContainsString(sprintf('Content with title %s was approved. You can view it at', $node->label()), preg_replace('/\s+/', ' ', $last['body']));
    // Check that empty tokens are removed.
    $this->assertStringNotContainsString('[node:field_does_not_exist]', preg_replace('/\s+/', ' ', $prev['body']));
    $this->assertStringNotContainsString('[node:field_does_not_exist]', preg_replace('/\s+/', ' ', $last['body']));
    $this->assertStringContainsString($node->toUrl('canonical', ['absolute' => TRUE])->toString(), preg_replace('/\s+/', ' ', $prev['body']));
    $this->assertStringContainsString($node->toUrl('canonical', ['absolute' => TRUE])->toString(), preg_replace('/\s+/', ' ', $last['body']));

    // Test again with node that was previously published.
    // Log back in as editor.
    $this->drupalLogin($this->editor);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm(['moderation_state[0][state]' => 'draft'], 'Save');
    // Reset collected email.
    $this->container->get('state')->set('system.test_mail_collector', []);
    // And now request a review.
    $this->submitForm(['new_state' => 'needs_review'], 'Apply');
    $this->assertNeedsReviewNotifications($node);

    // Try with the other node type, that isn't enabled.
    $this->container->get('state')->set('system.test_mail_collector', []);
    $this->drupalGet('node/add/another');
    $this->submitForm([
      'title[0][value]' => 'Another test node',
      'moderation_state[0][state]' => 'draft',
    ], 'Save');
    $node = $this->getNodeByTitle('Another test node');
    // Transition to needs review.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm(['moderation_state[0][state]' => 'needs_review'], 'Save');
    // No mail should be sent.
    $captured_emails = $this->container->get('state')->get('system.test_mail_collector') ?: [];
    $this->assertEmpty($captured_emails);
  }

  /**
   * Assert notifications sent for needs review.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node updated.
   */
  protected function assertNeedsReviewNotifications(NodeInterface $node) {
    // Check mail goes to approvers.
    $captured_emails = $this->container->get('state')->get('system.test_mail_collector') ?: [];
    // Should only be two emails.
    $this->assertCount(2, $captured_emails);
    $last = end($captured_emails);
    $prev = prev($captured_emails);
    $mails = [$last['to'], $prev['to']];
    sort($mails);
    $expected = [$this->approver1->getEmail(), $this->approver2->getEmail()];
    sort($expected);
    $this->assertEquals($expected, $mails);

    // The node id text is added to the email subject in the
    // workbench_email_test_mail_alter() function.
    // We check that it is set here.
    $this->assertEquals(sprintf('Content needs review: %s (node id: %s)', $node->label(), $node->id()), preg_replace('/\s+/', ' ', $last['subject']));
    $this->assertEquals(sprintf('Content needs review: %s (node id: %s)', $node->label(), $node->id()), preg_replace('/\s+/', ' ', $prev['subject']));

    $this->assertEquals($this->editor->getEmail(), $last['reply-to']);
    $this->assertEquals($this->editor->getEmail(), $prev['reply-to']);
    $this->assertStringContainsString(sprintf('Content with title %s needs review. You can view it at', $node->label()), preg_replace('/\s+/', ' ', $prev['body']));
    $this->assertStringContainsString(sprintf('Content with title %s needs review. You can view it at', $node->label()), preg_replace('/\s+/', ' ', $last['body']));
    // Check that empty tokens are removed.
    $this->assertStringNotContainsString('[node:field_does_not_exist]', preg_replace('/\s+/', ' ', $prev['body']));
    $this->assertStringNotContainsString('[node:field_does_not_exist]', preg_replace('/\s+/', ' ', $last['body']));
    $this->assertStringContainsString($node->toUrl('canonical', ['absolute' => TRUE])->toString(), preg_replace('/\s+/', ' ', $prev['body']));
    $this->assertStringContainsString($node->toUrl('canonical', ['absolute' => TRUE])->toString(), preg_replace('/\s+/', ' ', $last['body']));
  }

}
