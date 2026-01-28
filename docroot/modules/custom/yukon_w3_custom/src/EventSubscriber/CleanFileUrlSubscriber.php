<?php

namespace Drupal\yukon_w3_custom\EventSubscriber;

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to serve files without date folders in URL.
 */
class CleanFileUrlSubscriber implements EventSubscriberInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a CleanFileUrlSubscriber object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run with high priority to intercept before other file handling.
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];
    return $events;
  }

  /**
   * Handles file requests without date folders.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $path = $request->getPathInfo();

    // Only process requests to /sites/default/files/filename.ext (without date folder).
    // Pattern: /sites/default/files/[filename] where filename doesn't start with YYYY-MM/.
    if (preg_match('#^/sites/default/files/([^/]+)$#', $path, $matches)) {
      $filename_encoded = $matches[1];

      // Skip if this looks like a date folder path (YYYY-MM/filename).
      if (preg_match('#^\d{4}-\d{2}/#', $filename_encoded)) {
        return;
      }

      // URL decode the filename (e.g., %20 becomes space).
      $filename = urldecode($filename_encoded);

      // Find the actual file in date folders.
      $actual_file_path = $this->findFileInDateFolders($filename);

      if ($actual_file_path && file_exists($actual_file_path)) {
        // Serve the file.
        $response = new BinaryFileResponse($actual_file_path);
        $response->setContentDisposition('inline', basename($filename));
        $event->setResponse($response);
      }
    }
  }

  /**
   * Finds a file in date-organized folders.
   *
   * Searches for the most recent version of a file across date folders.
   *
   * @param string $filename
   *   The filename to search for.
   *
   * @return string|null
   *   The full path to the file, or NULL if not found.
   */
  protected function findFileInDateFolders($filename) {
    $files_directory = DRUPAL_ROOT . '/sites/default/files';

    if (!is_dir($files_directory)) {
      return NULL;
    }

    $found_files = [];

    // Scan for date-pattern directories (YYYY-MM).
    $directories = glob($files_directory . '/[0-9][0-9][0-9][0-9]-[0-9][0-9]', GLOB_ONLYDIR);

    if ($directories) {
      // Sort directories in reverse chronological order (newest first).
      rsort($directories);

      foreach ($directories as $dir) {
        $file_path = $dir . '/' . $filename;
        if (file_exists($file_path)) {
          // Return the first (most recent) match.
          return $file_path;
        }
      }
    }

    // Also check root files directory for files without date folders.
    $root_file_path = $files_directory . '/' . $filename;
    if (file_exists($root_file_path)) {
      return $root_file_path;
    }

    return NULL;
  }

}
