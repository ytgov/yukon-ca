<?php

namespace Drupal\advanced_help;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\File\FileSystemInterface;

/**
 * AdvancedHelp plugin manager.
 */
class AdvancedHelpManager extends DefaultPluginManager {
  use StringTranslationTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs an AdvancedHelpManager instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   Theme handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file handler.
   */

  public function __construct(ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, CacheBackendInterface $cache_backend, TranslationInterface $string_translation, FileSystemInterface $file_system) {
    $this->module_handler = $module_handler;
    $this->theme_handler = $theme_handler;
    $this->setStringTranslation($string_translation);
    $this->alterInfo('advanced_help');
    $this->setCacheBackend($cache_backend, 'advanced_help', ['advanced_help']);
    $this->fileSystem = $file_system;
  }

  /**
   * Get the modules/theme list.
   *
   * @todo cache
   */
  public function getModuleList() {
    $modules = $this->module_handler->getModuleList();
    $themes = $this->theme_handler->listInfo();
    $result = [];

    foreach ($modules as $name => $data) {
      $result[$name] = $this->module_handler->getName($name);
    }

    foreach ($themes as $name => $data) {
      $result[$name] = $this->theme_handler->getName($name);
    }

    return $result;
  }

  /**
   * Get the information for a single help topic.
   *
   * @param $module
   * @param $topic
   *
   * @return string|bool
   */
  public function getTopic($module, $topic) {
    $topics = $this->getTopics();
    if (!empty($topics[$module][$topic])) {
      return $topics[$module][$topic];
    }
    return FALSE;
  }

  /**
   * Return the name of the project.
   *
   * @param string $project
   *
   * @return string
   */
  public function getProjectName($project) {
    if ($this->module_handler->moduleExists($project)) {
      return $this->module_handler->getName($project);
    }
    else {
      return $this->theme_handler->getName($project);
    }
  }

  /**
   * Search the system for all available help topics.
   *
   * @todo check visibility of the method.
   */
  public function getTopics() {
    $ini = $this->parseHelp();
    return $ini['topics'];
  }

  /**
   * Returns advanced help settings.
   *
   * @todo check visibility of the method.
   */
  public function getSettings() {
    $ini = $this->parseHelp();
    return $ini['settings'];
  }

  /**
   * Function to parse yml / txt files.
   *
   * @todo implement cache
   * @todo check visibility of the method.
   */
  public function parseHelp() {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    static $ini = NULL;

    $cache = $this->cacheGet('advanced_help_ini_' . $language);
    if ($cache) {
      $ini = $cache->data;
    }

    if (!isset($ini)) {
      $ini = ['topics' => [], 'settings' => []];

      foreach ($this->module_handler->getModuleList() + $this->theme_handler->listInfo() as $plugin_name => $extension) {
        $module = $plugin_name;
        $module_path = $extension->getPath();
        $info = [];

        // Full Advanced Help help text
        if (file_exists($module_path . '/help/' . $module . '.help.yml')) {
          $path = $module_path . '/help';
          $info = Yaml::decode(file_get_contents($module_path . '/help/' . $module . '.help.yml'));
        }
        elseif (!file_exists($module_path . '/help')) {
          // Look for one or more README files.
          $files = $this->fileSystem->scanDirectory('./' . $module_path,
            '/^(readme).*\.(txt|md)$/i', ['recurse' => FALSE]);
          $path = $module_path;
          foreach ($files as $name => $fileinfo) {
            $info[$fileinfo->filename] = [
              'line break' => TRUE,
              'file' => $fileinfo->filename,
              'title' => $fileinfo->name,
            ];
	    // $info['path'] = $path;
          }
        }

        if (!empty($info)) {
          // Get translated titles:
          $translation = [];
          if (file_exists("$module_path/translations/help/$language/$module.help.yml")) {
            $translation = Yaml::decode(file_get_contents("$module_path/translations/help/$language/$module.help.yml"));
          }

          $ini['settings'][$module] = [];
          if (!empty($info['advanced help settings'])) {
            $ini['settings'][$module] = $info['advanced help settings'];
            unset($info['advanced help settings']);

            // Check translated strings for translatable global settings.
            if (isset($translation['advanced help settings']['name'])) {
              $ini['settings']['name'] = $translation['advanced help settings']['name'];
            }
            if (isset($translation['advanced help settings']['index name'])) {
              $ini['settings']['index name'] = $translation['advanced help settings']['index name'];
            }

          }

          foreach ($info as $name => $topic) {

            // Each topic should have a name, a title, a path and a filename.
            $file = !empty($topic['file']) ? $topic['file'] : $name;

            $fname = $path . '/' . $file;

            if (file_exists($fname)) {
              $file_name = $file;
	    }
            elseif (file_exists($fname . '.html')) {
              $file_name = $file . '.html';
	    }
	    elseif (file_exists($fname . '.md')) {
              $file_name = $file . '.md';
	    }
	    elseif  (file_exists($fname . '.txt')) {
              $file_name = $file . '.txt';
            }
	    else {
              $file_name = 'MISSING';
	    }

            $ini['topics'][$module][$name] = [
              'name' => $name,
              'module' => $module,
              'ini' => $topic,
              'title' => !empty($translation[$name]['title']) ? $translation[$name]['title'] : $topic['title'],
              'weight' => isset($topic['weight']) ? $topic['weight'] : 0,
              'parent' => isset($topic['parent']) ? $topic['parent'] : 0,
              'popup width' => isset($topic['popup width']) ? $topic['popup width'] : 500,
              'popup height' => isset($topic['popup height']) ? $topic['popup height'] : 500,
              // Require extension.
              'file' => $file_name,
              // Not in .ini file.
              'path' => $path,
              'line break' => isset($topic['line break']) ? $topic['line break'] : (isset($ini['settings'][$module]['line break']) ? $ini['settings'][$module]['line break'] : FALSE),
              'navigation' => isset($topic['navigation']) ? $topic['navigation'] : (isset($ini['settings'][$module]['navigation']) ? $ini['settings'][$module]['navigation'] : TRUE),
              'css' => isset($topic['css']) ? $topic['css'] : (isset($ini['settings'][$module]['css']) ? $ini['settings'][$module]['css'] : NULL),
	      'file name' => $file_name,
            ];
          }
        }
      }
      $this->cacheSet('advanced_help_ini_' . $language, $ini);
    }
    return $ini;
  }

  /**
   * Load and render a help topic.
   *
   * @todo allow the theme override the help.
   * @param $module
   * @param $topic
   *
   * @return array
   */
  public function getTopicFileInfo($module, $topic) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $info = $this->getTopic($module, $topic);
    if (empty($info)) {
      return FALSE;
    }

    $path_type = (preg_match('/themes/', $info['path'])) ? 'theme' : 'module';
    // Search paths:
    $paths = [
    // // Allow theme override.
    //      path_to_theme() . '/help',
      // Translations.
      \Drupal::service('extension.path.resolver')->getPath($path_type, $module) . "/translations/help/$language",
      // In same directory as .inc file.
      $info['path'],
    ];

    foreach ($paths as $path) {
      if (file_exists($path . '/' . $info['file'])) {
        return ['path' => $path, 'file' => $info['file']];
      }
    }

    return FALSE;
  }

  /**
   *
   */
  public function getTopicFileName($module, $topic) {
    $info = $this->getTopicFileInfo($module, $topic);
    if ($info) {
      return '/' . $info['path'] . '/' . $info['file'];
    }
  }

}
