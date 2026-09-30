The following needs to be added to your settings.local.php file:

```
$config['search_api.server.yg_solr_9']['backend_config']['connector_config']['host'] = 'solr';
$config['search_api.server.yg_solr_9']['backend_config']['connector_config']['port'] = 8983;
$config['search_api.server.yg_solr_9']['backend_config']['connector_config']['password'] = 'SolrRocks';
```

Everything else in the `yg_solr_9` server config (connector `solr_cloud_basic_auth`, username `solr9`,
`distrib`, `checkpoints_collection`, etc.) already matches ddev's Solr setup, so it doesn't need overriding.

After adding this and running `ddev restart`, upload the configset via the Search API Solr admin UI
(`/admin/config/search/search-api/server/yg_solr_9`) to create the `yukon_ca_dev_d10` collection in ddev's Solr.
