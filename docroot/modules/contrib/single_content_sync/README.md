# Single Content Sync

A simple way to export/import a node content with all entity references.

## Export content

### Which entity references can be exported?

Here is a current list of supported entity references which can be exported/imported:

- Taxonomy term
- Node
- Block content
- User
- Media
- Paragraphs

This list can be extended by you, see how to do it below.

### Can I extend exporting of my custom entity?

Yes! You can use a hook to alter exporting of entity.

```
hook_content_export_entity_alter(array &$base_fields, FieldableEntityInterface $entity)
```

It allows you to export base fields of your custom entity or alter based fields of supported entity.

### Can I extend exporting of my custom field type?

Yes! You can use a hook to alter exporting of field value.

```
hook_content_export_field_value_alter(&$value, FieldItemListInterface $field)
```

## Import content

### Can I extend importing of my custom entity?

Yes! You can use a hook to alter importing of entity.

```
hook_content_import_entity_alter(array $content, FieldableEntityInterface $entity)
```

### Can I extend importing of my custom field type?

Yes! You can use a hook to alter importing of field value.

```
hook_content_import_field_value_alter(&$value, FieldItemListInterface $field)
```

### Can I import my content on deploy?

Yes! Please use the importer service and hook_update_N or similar to do it. Check it out

```php
function example_update_8001() {
  $file_path = \Drupal::service('extension.list.module')->getPath('example') . '/assets/homepage.yml';
  \Drupal::service('single_content_sync.importer')->importFromFile($file_path);
}
```

If you would like to import content from a generated zip file, use the following code:

```php
function example_update_8001() {
  $file_path = \Drupal::service('extension.list.module')->getPath('example') . '/assets/homepage.zip';
  \Drupal::service('single_content_sync.importer')->importFromZip($file_path);
}
```

## Documentation

Check out this guide to see the module’s overview and the guidelines for using it.

https://www.drupal.org/docs/contributed-modules/single-content-sync
