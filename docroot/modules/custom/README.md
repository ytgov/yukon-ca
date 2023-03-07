### Migration issues on the pantheon.

##### Problem

Unfortunately, when we will try to check the list of migrations(status), or import group of migrations, pantheon will
throw an `"Allowing memory size error"`.

##### Solution

Run the migration status or migration import drush command against single migration item.

###### Multiple migrations workaround.

We can use next command to see the status for all migrations on environment:

Evolvingweb:
```
drush @evolvingweb ms yukon_migrate_category;
drush @evolvingweb ms yukon_migrate_department;
drush @evolvingweb ms yukon_migrate_engagement_categories;
drush @evolvingweb ms yukon_migrate_media_folders;
drush @evolvingweb ms yukon_migrate_teams;
drush @evolvingweb ms yukon_migrate_charts;
drush @evolvingweb ms yukon_migrate_collapsable_field;
drush @evolvingweb ms yukon_migrate_image_gallery;
drush @evolvingweb ms yukon_migrate_multi_step;
drush @evolvingweb ms yukon_migrate_basic_page;
drush @evolvingweb ms yukon_migrate_basic_page_translations;
drush @evolvingweb ms yukon_migrate_documents_page;
drush @evolvingweb ms yukon_migrate_documents_page_translations;
drush @evolvingweb ms yukon_migrate_engagement;
drush @evolvingweb ms yukon_migrate_engagement_translations;
drush @evolvingweb ms yukon_migrate_in_page_alert;
drush @evolvingweb ms yukon_migrate_in_page_alert_translations;
drush @evolvingweb ms yukon_migrate_multi_step_page;
drush @evolvingweb ms yukon_migrate_multi_step_page_translations;
drush @evolvingweb ms yukon_migrate_topics_page;
drush @evolvingweb ms yukon_migrate_topics_page_translations;
```

Simply update `ms` with `mim` command if you want to run all of these migrations "simultaneously"
