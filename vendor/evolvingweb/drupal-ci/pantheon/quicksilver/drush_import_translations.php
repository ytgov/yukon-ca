<?php

$override = 'not-customized';
if (!empty($argv[1])) {
  $allowed_values = [
    'none',
    'customized',
    'not-customized',
    'all',
  ];
  if (in_array($argv[1], $allowed_values)) {
    $override = $argv[1];
  }
}
echo "Get translations folder...\n";
$script_dir = dirname(__FILE__);
// Get the path to the drupal root depending on whether we're on Pantheon or not.
if (!empty($_POST['environment'])) {
  $drupal_root = "${script_dir}/../../../";
}
else {
  $drupal_root = "${script_dir}/../../../../../";
}
echo "Drupal root: $drupal_root\n";
$translations_folder = "${drupal_root}/translations/";
if (file_exists($translations_folder)) {
  echo "Iterate through files...\n";
  $files = scandir($translations_folder);
  $ignore_files = ['.', '..'];
  foreach ($files as $file) {
    if (!in_array($file, $ignore_files)) {
      $language = substr($file, 0, 2);
      $command = "drush locale-import ${language} ${translations_folder}${file} --override=${override}";
      echo "Running ${command} ...\n";
      passthru($command);
    }
  }
}
else {
  echo "No translations folder. Exit\n";
}