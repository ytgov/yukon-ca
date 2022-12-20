<?php

/**
 * Database settings:
 */
$databases['default']['default'] = array(
  'driver' => 'mysql',
  'database' => !isset($_ENV['MYSQL_DATABASE']) ? 'drupal' : $_ENV['MYSQL_DATABASE'],
  'username' => !isset($_ENV['MYSQL_USER']) ? 'drupal' : $_ENV['MYSQL_USER'],
  'password' => !isset($_ENV['MYSQL_PASSWORD']) ? 'drupal' : $_ENV['MYSQL_PASSWORD'],
  'host' => 'db',
  'prefix' => '',
);

/**
 * Setup HTTP Basic Authentication. Leave blank to make publically accessible.
 */
if (!empty($_ENV['HTTP_AUTH_ENABLE']) && $_ENV['HTTP_AUTH_ENABLE'] == 'yes') {
  $settings['http_basic_auth']['user'] = !isset($_ENV['HTTP_AUTH_USER']) ? 'drupal' : $_ENV['HTTP_AUTH_USER'];
  $settings['http_basic_auth']['password'] = !isset($_ENV['HTTP_AUTH_PASS']) ? 'drupal' : $_ENV['HTTP_AUTH_PASS'];
}

/**
 * Trusted host configuration.
 */
$settings["trusted_host_patterns"] = array(
  '^docker',
  '^localhost$',
  '^ngrok\.io$',
);

