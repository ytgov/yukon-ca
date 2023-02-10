<?php

/**
 * @file
 * Migrate Scanner API documentation.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations on the migrate scanner regexp patterns (exclude/include).
 *
 * Please keep in mind that regexp patterns are applied to absolute file paths.
 *
 * @param string[][] $patterns
 *   An associate array containing the following keys:
 *   - include: an array of regexp patterns to include migration files;
 *   - exclude: an array of regexp patterns to exclude migration files.
 */
function hook_migrate_scanner_patterns_alter(array &$patterns) {
  // Exclude files located in migrations/state directories.
  $patterns['exclude'][] = '#/migrations/state/#';
  // Include migrations located only in migrations/d6 directories.
  $patterns['include'][] = '#/migrations/d6/#';
}

/**
 * @} End of "addtogroup hooks".
 */
