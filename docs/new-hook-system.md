# Drupal's new (OOP) hook system

Drupal 10.3 introduced an alternative to procedural `hook_NAME()` functions in `.module` files: hook implementations as attributed class methods. Stable since Drupal 11.1. This codebase does not use it yet — this doc is reference for when we do.

## Old vs new

Old (`mymodule.module`):

```php
function mymodule_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // ...
}
```

New (e.g. `src/Hook/MymoduleHooks.php`):

```php
namespace Drupal\mymodule\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Form\FormStateInterface;

class MymoduleHooks {
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // ...
  }
}
```

## How it works

Hook classes are services, discovered by core scanning for `#[Hook]` attributes and building a cached `hook_name → [service_id, method]` map. Because they're services, constructor dependency injection works instead of `\Drupal::service()` calls. The class is only instantiated when that specific hook fires, not eagerly.

`#[Hook('hook_name', order: 'first'|'last', module: 'other_module')]` controls ordering/targeting — replaces `hook_module_implements_alter()`.

Old-style procedural hooks still work. Both styles coexist in the same module.

## Deprecation timeline

Legacy procedural hooks — and `.module` files entirely — are slated for removal in **Drupal 13**. No fixed date as of this writing, but plan a migration pass before then rather than leaving it to the last minute.

## Automated conversion

[`palantirnet/drupal-rector`](https://github.com/palantirnet/drupal-rector) ships a `HookConvertRector` rule that converts procedural `hook_*()` implementations into `#[Hook]`-attributed classes under `src/Hook/`.

- Run it as its own pass, separate from the normal deprecation rector rules.
- It leaves legacy wrapper functions behind for compatibility rather than deleting the old ones — cleanup of the `.module` file is manual.
- Run this repo's `phpcs` wrapper afterward — generated code needs formatting/import cleanup to conform to Drupal coding standards.

## When to do this here

Not scheduled. Treat as a distinct migration pass sometime before Drupal 13, not bundled into routine feature/security-update work.

## Sources

- [Convert Drupal Hooks to Object‑Oriented Methods Using Rector](https://www.thedroptimes.com/66016/convert-drupal-hooks-object-oriented-methods-using-rector)
- [Converting legacy hooks to the new OOP system in Drupal 11.1+ | MD Systems GmbH](https://www.md-systems.ch/en/blog/2025-10-19/converting-legacy-hooks)
- [Support for object oriented hook implementations using autowired services; some ModuleHandler methods deprecated | Drupal.org](https://www.drupal.org/node/3442349)
