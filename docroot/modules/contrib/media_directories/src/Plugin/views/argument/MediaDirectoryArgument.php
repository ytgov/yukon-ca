<?php

namespace Drupal\media_directories\Plugin\views\argument;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Media directory argument plugin.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("media_directory")
 */
class MediaDirectoryArgument extends ArgumentPluginBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The actual value which is used for querying.
   *
   * @var array
   */
  public $value;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->definition = $plugin_definition + $configuration;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('config.factory'));
  }

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

    if ($this->value === [MEDIA_DIRECTORY_ROOT]) {
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
    $config = $this->configFactory->get('media_directories.settings');

    $placeholder = $this->placeholder();
    $null_check = empty($this->options['not']) ? '' : " OR $this->tableAlias.$this->realField IS NULL";

    $new_group = $this->query->setWhereGroup();
    if ((int) $this->argument === MEDIA_DIRECTORY_ROOT) {
      if ($config->get('all_files_in_root')) {
        // Show everything.
        $this->query->setWhereGroup('OR', $new_group);
        $this->query->addWhereExpression($new_group, "$this->tableAlias.$this->realField IS NOT NULL");
      }
      $this->query->addWhereExpression($new_group, "$this->tableAlias.$this->realField IS NULL");
    }
    else {
      $operator = empty($this->options['not']) ? '=' : '!=';
      $this->query->addWhereExpression($new_group, "$this->tableAlias.$this->realField $operator $placeholder" . $null_check, [$placeholder => $this->argument]);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getSortName() {
    return $this->t('Numerical', [], ['context' => 'Sort order']);
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
    return new ContextDefinition('integer', $this->adminLabel(), FALSE);
  }

}
