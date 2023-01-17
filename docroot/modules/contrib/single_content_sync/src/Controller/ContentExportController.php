<?php

namespace Drupal\single_content_sync\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines controller to download file.
 *
 * @package Drupal\single_content_sync\Controller
 */
class ContentExportController extends ControllerBase {

  /**
   * Download an exported file.
   */
  public function download() {
    $file_name = \Drupal::request()->query->get('file');
    $files = $this->entityTypeManager()->getStorage('file')
      ->loadByProperties(['filename' => $file_name]);

    if (!$files) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\file\FileInterface $file */
    $file = array_pop($files);
    return new BinaryFileResponse($file->getFileUri(), 200, [], FALSE, 'attachment');
  }

}
