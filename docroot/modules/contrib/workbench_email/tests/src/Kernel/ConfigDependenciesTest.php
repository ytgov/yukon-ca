<?php

namespace Drupal\Tests\workbench_email\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\user\Entity\Role;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\workbench_email\Traits\WorkbenchEmailTestTrait;
use Drupal\workbench_email\Entity\Template;

/**
 * Defines a class for testing config dependencies.
 *
 * @group workbench_email
 */
class ConfigDependenciesTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use ContentModerationTestTrait;
  use WorkbenchEmailTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'text',
    'system',
    'user',
    'workbench_email',
    'workflows',
    'content_moderation',
    'field',
  ];

  /**
   * The template being tested.
   *
   * @var \Drupal\workbench_email\TemplateInterface
   */
  protected $template;

  /**
   * The editor role.
   *
   * @var \Drupal\user\Entity\Role
   */
  protected $editorRole;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig([
      'node',
      'workflows',
      'content_moderation',
      'workbench_email',
      'system',
    ]);
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $node_type = $this->createContentType(['type' => 'test']);
    $this->setUpEmailFieldForNodeBundle();
    $this->editorRole = Role::create(['id' => 'editor', 'label' => 'editor']);
    $this->editorRole->save();
    $this->template = $this->setUpTemplate();
    $this->createEditorialWorkflow();
  }

  /**
   * Tests scheme dependencies.
   */
  public function testSchemeDependencies() {
    $this->assertEquals([
      'config' => [
        'field.storage.node.field_email',
        'user.role.editor',
      ],
    ], $this->template->getDependencies());

    // Delete the editor role.
    $this->editorRole->delete();
    $this->template = $this->loadUnchangedTemplate($this->template->id());
    $this->assertEquals([
      'config' => [
        'field.storage.node.field_email',
        'workflows.workflow.editorial',
      ],
    ], $this->template->getDependencies());

    // Delete the email field.
    FieldConfig::load('node.test.field_email')->delete();
    $this->template = $this->loadUnchangedTemplate($this->template->id());
    $this->assertEquals([
      'config' => [
        'workflows.workflow.editorial',
      ],
    ], $this->template->getDependencies());
  }

  /**
   * Creates a test email template.
   *
   * @param string $id
   *   The id for the template.
   *
   * @return \Drupal\workbench_email\Entity\Template
   *   Created template.
   */
  protected function setUpTemplate($id = 'test_template') {
    $template = Template::create([
      'id' => $id,
      'label' => ucfirst(str_replace('_', ' ', $id)),
      'recipient_types' => [
        'role' => [
          'id' => 'role',
          'provider' => 'workbench_email',
          'status' => 1,
          'settings' => [
            'roles' => [
              'editor' => 'editor',
            ],
          ],
        ],
        'author' => [
          'id' => 'author',
          'provider' => 'workbench_email',
          'status' => 1,
          'settings' => [],
        ],
        'email' => [
          'id' => 'email',
          'provider' => 'workbench_email',
          'status' => 1,
          'settings' => [
            'fields' => [
              'node:field_email',
            ],
          ],
        ],
      ],
      'transitions' => [
        'editorial' => [
          'publish' => 'publish',
        ],
      ],
    ]);
    $template->save();
    return $template;
  }

  /**
   * Loads the given template.
   *
   * @param string $template_id
   *   Template ID.
   *
   * @return \Drupal\workbench_email\TemplateInterface
   *   Unchanged scheme.
   */
  protected function loadUnchangedTemplate($template_id) {
    return $this->container->get('entity_type.manager')
      ->getStorage('workbench_email_template')
      ->loadUnchanged($template_id);
  }

}
