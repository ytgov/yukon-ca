<?php

/**
 * @file
 * Contains post update hooks.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\workbench_email\Entity\Template;
use Drupal\workbench_email\TemplateInterface;
use Drupal\workflows\Entity\Workflow;

/**
 * Implements hook_removed_post_updates().
 */
function workbench_email_removed_post_updates() {
  return [
    'workbench_email_post_update_move_to_recipient_plugins' => '2.2.2',
    'workbench_email_post_update_add_reply_to' => '2.2.2',
  ];
}

/**
 * Update config entities with the new template format option.
 */
function workbench_email_post_update_add_template_format(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'workbench_email_template', function (TemplateInterface $template): bool {
      // Previously, we sent all emails in HTML if swiftmailer was installed.
      // For BC, update each template to HTML only if swiftmailer is installed.
      $format = \Drupal::moduleHandler()->moduleExists('swiftmailer') ? 'html' : 'plain_text';
      $template->set('format', $format);
      return TRUE;
    }
  );
}

/**
 * Update the config schema to make email body translatable.
 */
function workbench_email_post_update_make_email_body_translatable(&$sandbox = NULL) { }

/**
 * Migrate transition mapping from workflow to template.
 */
function workbench_email_post_update_move_transition_mapping_to_template_config() {
  foreach (Workflow::loadMultiple() as $workflow) {
    foreach ($workflow->getThirdPartySetting('workbench_email', 'workbench_email_templates') as $transition => $templates) {
      foreach (Template::loadMultiple($templates) as $template) {
        $templateTransitions = $template->getTransitions();
        $templateTransitions[$workflow->id()][$transition] = $transition;
        $template->setTransitions($templateTransitions);
        $template->save();
      }
    }
    $dependencies = $workflow->get('dependencies');
    if (isset($dependencies['enforced']['config'])) {
      $dependencies['enforced']['config'] = array_filter($dependencies['enforced']['config'], function (string $config) {
        return !str_starts_with($config, 'workbench_email.workbench_email_template.');
      });
    }
    $workflow->unsetThirdPartySetting('workbench_email', 'workbench_email_templates');
    $workflow->set('dependencies', $dependencies);
    $workflow->save();
  }
}
