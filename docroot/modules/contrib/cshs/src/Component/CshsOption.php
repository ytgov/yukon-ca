<?php

namespace Drupal\cshs\Component;

use Drupal\cshs\Element\CshsElement;

/**
 * The container for representing the `cshs` option.
 */
class CshsOption {

  /**
   * Creates the option instance.
   *
   * @param string $label
   *   The option's label.
   * @param string|null $parent
   *   The option's parent.
   * @param string|null $group
   *   The option's group label.
   */
  public function __construct(
    protected string $label,
    protected ?string $parent = NULL,
    protected ?string $group = NULL,
  ) {
  }

  /**
   * Returns the options list for `cshs.html.twig`.
   *
   * @param array $element
   *   The `cshs` element to process options of.
   *
   * @return array
   *   The options list.
   *
   * @throws \RuntimeException
   *   When the children of `$element['#options']` cannot be
   *   converted or not instances of this class.
   *
   * @see \Drupal\cshs\Element\CshsElement::preRender()
   * @see \form_select_options()
   */
  public static function formatOptions(array $element): array {
    $list = [];
    $has_value = \array_key_exists('#value', $element);
    $is_value_array = $has_value && \is_array($element['#value']);

    foreach ($element['#options'] ?? [] as $value => $option) {
      // The default `All` option coming from Views has `$option` as a string.
      if (\is_string($option)) {
        $option = new static($option);
      }
      elseif (\is_array($option)) {
        @\trigger_error(
          \implode(\PHP_EOL, [
            'The support of old-fashioned options for CSHS is deprecated in cshs:2.1 and is removed in cshs:3.0.',
            "Replace \"\$element['#options'] = [12 => ['name' => 'Audi Q8', 'parent_tid' => '4']];\"",
            "for \"\$element['#options'] = [12 => new CshsOption('Audi Q8', '4')];\".",
          ]),
          \E_USER_DEPRECATED,
        );

        $option = new static($option['name'] ?? '', $option['parent_tid'] ?? NULL, $option['group'] ?? NULL);
      }
      elseif (!($option instanceof static)) {
        throw new \RuntimeException(\sprintf(
          'The "%s" of the "%s" element must be instances of "%s".',
          "\$element['#options']",
          CshsElement::ID,
          static::class,
        ));
      }

      if ($has_value) {
        // The type-unsafe comparison is intentional.
        /* @noinspection TypeUnsafeComparisonInspection */
        $selected = $is_value_array
          ? \in_array($value, $element['#value'], FALSE)
          : $value == $element['#value'];
      }
      else {
        $selected = FALSE;
      }

      $item = [
        'type' => 'option',
        'value' => $value,
        'label' => $option->label,
        'parent' => $option->parent,
        'selected' => $selected,
      ];

      if ($option->group) {
        if (empty($list[$option->group])) {
          $list[$option->group] = [
            'type' => 'optgroup',
            'label' => $option->group,
            'options' => [],
          ];
        }

        $list[$option->group]['options'][] = $item;
      }
      else {
        $list[] = $item;
      }
    }

    return \array_values($list);
  }

}
