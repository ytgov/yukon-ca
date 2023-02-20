<?php

namespace Drupal\media_directories_ui\Plugin\views\argument;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Media directory ui string contains argument plugin.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("media_directory_ui_string_contains")
 */
class StringContainsArgument extends ArgumentPluginBase {

  /**
   * The actual value which is used for querying.
   *
   * @var array
   */
  public $value;

  /**
   * {@inheritdoc}
   */
  public function title() {
    if (!$this->argument) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : $this->t('Uncategorized');
    }

    if (!empty($this->options['break_phrase'])) {
      $break = static::breakString($this->argument, FALSE);
      $this->value = $break->value;
      $this->operator = $break->operator;
    }
    else {
      $this->value = [$this->argument];
      $this->operator = 'or';
    }

    if (empty($this->value)) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : $this->t('Uncategorized');
    }

    if ($this->value === ['']) {
      return !empty($this->definition['invalid input']) ? $this->definition['invalid input'] : $this->t('Invalid input');
    }

    return implode($this->operator == 'or' ? ' + ' : ', ', $this->titleQuery());
  }

  /**
   * Override for specific title lookups.
   *
   * @return array
   *   Returns all titles, if it's just one title it's an array with one entry.
   */
  public function titleQuery() {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    $this->value = $this->argument;
    $placeholder = $this->placeholder();
    // empty($this->options['not']) ? '' : " OR $this->tableAlias.$this->realField IS NULL";.
    $null_check = '';

    if (!empty($this->value)) {
      $operator = empty($this->options['not']) ? 'LIKE' : 'NOT LIKE';
      $new_group = $this->query->setWhereGroup();
      $this->query->addWhereExpression($new_group, "$this->tableAlias.$this->realField $operator $placeholder" . $null_check, [$placeholder => '%' . $this->argument . '%']);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getSortName() {
    return $this->t('Alphabetical', [], ['context' => 'Sort order']);
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDefinition() {
    if ($context_definition = parent::getContextDefinition()) {
      return $context_definition;
    }

    // If the parent does not provide a context definition through the
    // validation plugin, fall back to the integer type.
    return new ContextDefinition('string', $this->adminLabel(), FALSE);
  }

}
