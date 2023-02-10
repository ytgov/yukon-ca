<?php

namespace Drupal\migrate_scanner\Component\Discovery;

use Drupal\Component\Discovery\YamlDirectoryDiscovery;

/**
 * Discovers multiple YAML files in a set of directories and sub-directories.
 */
class YamlRecursiveDirectoryDiscovery extends YamlDirectoryDiscovery {

  /**
   * The regex patterns used to exclude/include files or directories.
   *
   * Expected array keys are 'include' and 'exclude'.
   *
   * @var string[][]
   */
  protected $patterns = ['include' => [], 'exclude' => []];

  /**
   * Constructs a YamlRecursiveDirectoryDiscovery object.
   *
   * @param array $directories
   *   An array of directories to scan, keyed by the provider. The value can
   *   either be a string or an array of strings. The string values should be
   *   the path of a directory to scan.
   * @param string $file_cache_key_suffix
   *   The file cache key suffix. This should be unique for each type of
   *   discovery.
   * @param string $key
   *   (optional) The key contained in the discovered data that identifies it.
   *   Defaults to 'id'.
   * @param string[][] $patterns
   *   (optional) A two-dimensional array of regexp patterns (each array is
   *   keyed by pattern type - include or exclude) to filter migrate files.
   */
  public function __construct(array $directories, $file_cache_key_suffix, $key = 'id', array $patterns = []) {
    parent::__construct($directories, $file_cache_key_suffix, $key);
    $this->patterns = array_merge($this->patterns, $patterns);
  }

  /**
   * Gets an iterator to loop over the files in the provided directory.
   *
   * It'll loop over given directory and their sub-directories recursively.
   *
   * @param string $directory
   *   The directory to scan.
   *
   * @return \Traversable
   *   An \Traversable object or array where the values are \SplFileInfo
   *   objects.
   */
  protected function getDirectoryIterator($directory) {
    $dir_iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
    return new \RegexIterator($dir_iterator, '/\.yml$/i');
  }

  /**
   * Returns an array of providers keyed by file path.
   *
   * The files are filtered by a list of supplied patterns (include or exclude).
   *
   * @return array
   *   An array of providers keyed by file path.
   */
  protected function findFiles() {
    $file_list = parent::findFiles();

    // There is nothing to do here if both patterns are not present.
    if (empty($this->patterns['exclude']) && empty($this->patterns['include'])) {
      return $file_list;
    }

    $files_paths = empty($this->patterns['include']) ? array_keys($file_list) : [];

    // Keep a list of files that matched 'include' pattern.
    foreach ($this->patterns['include'] as $include_pattern) {
      $files_paths += preg_grep($include_pattern, array_keys($file_list));
    }

    // Remove files that matched 'exclude' pattern from the list.
    foreach ($this->patterns['exclude'] as $exclude_pattern) {
      $exclude_files = preg_grep($exclude_pattern, $files_paths);
      $files_paths = array_diff($files_paths, $exclude_files);
    }

    return array_intersect_key($file_list, array_flip($files_paths));
  }

}
