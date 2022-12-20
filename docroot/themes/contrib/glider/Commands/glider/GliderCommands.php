<?php

namespace Drush\Commands\glider;

use Drush\Commands\DrushCommands;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Glider commands.
 */
class GliderCommands extends DrushCommands {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\Drupal\Core\Extension\ThemeHandler
   */
  protected $themeHandler;

  /**
   * Symfony filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $filesystem;

  /**
   * Construct.
   */
  public function __construct() {
    $this->filesystem = new Filesystem();
  }

  /**
   * Creates a new subtheme based on glider.
   *
   * @param string $name
   *   Subtheme name. It should a machine name less than 16 characters.
   *
   * @usage glider:subtheme foo
   *   Create subtheme named foo.
   *
   * @command glider:subtheme
   * @aliases glider
   * @bootstrap full
   */
  public function subtheme($name) {
    if (!$this->themeHandler) {
      $this->themeHandler = \Drupal::service('theme_handler'); // phpcs:ignore
    }

    if (!\preg_match('/^[a-z][a-z_\d]{2,49}$/', $name)) {
      $this->logger()->error(dt('Theme name should start with a letter and can contain lowercase letters, numbers and underscore. It should be between 3 and 16 characters'));
      return;
    }
    if ($this->themeHandler->themeExists('glider')) {
      $theme_object = $this->themeHandler->getTheme('glider');
      $theme_path = DRUPAL_ROOT . '/' . $theme_object->subpath;
      $subtheme_path = DRUPAL_ROOT . '/themes/custom/' . $name;
      try {
        $this->filesystem->mirror($theme_path . '/starterkit', $subtheme_path);
      }
      catch (IOExceptionInterface $exception) {
        $this->logger()->error(dt('An error occurred while creating your directory at ' . $exception->getPath()));
      }

      $search_replace = [
        'starterkit.info.yml' => [
          'STARTERKIT' => $name,
          'Glider Sub-theme Starter Kit' => ucfirst(str_replace('_', ' ', $name)),
          "hidden: true\n" => '',
        ],
        'theme-settings.php' => [
          'starterkit' => $name,
        ],
        'starterkit.theme' => [
          'starterkit' => $name,
        ],
      ];

      foreach ($search_replace as $file => $values) {
        foreach ($values as $search => $replace) {
          $file_contents = file_get_contents($subtheme_path . '/' . $file);
          $file_contents = str_replace($search, $replace, $file_contents);
          file_put_contents($subtheme_path . '/' . $file, $file_contents);
        }
      }

      $files_to_rename = [
        'starterkit.info.yml' => $name . '.info.yml',
        'starterkit.libraries.yml' => $name . '.libraries.yml',
        'starterkit.theme' => $name . '.theme',
      ];

      foreach ($files_to_rename as $old_name => $new_name) {
        try {
          $this->filesystem->rename($subtheme_path . '/' . $old_name, $subtheme_path . '/' . $new_name);
        }
        catch (IOExceptionInterface $exception) {
          $this->logger()->error(dt('An error occurred while renaming your file at ' . $exception->getPath()));
        }
      }
      $this->logger()->success(dt('Subtheme ' . $name . ' successfully created.'));
    }
  }

}
