<?php

namespace Drupal\submit_diginole_ais;

use \Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use \Drupal\file\FileRepositoryInterface;

/**
 * class SubmitDiginoleFileService
 */
class SubmitDiginoleFileService {

  /**
   * File System Interface
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * File repository interface
   *
   * @var \Drupal\file\FileRepositoryInterface;
   */
  protected $fileRepository;

  /**
   * Constructs a new SubmitDiginoleFileService
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *  File system interface
   * @param \Drupal\file\FileRepositoryInterface $fileRepository
   *  File repository service
   */
  public function __construct(FileSystemInterface $fileSystem, FileRepositoryInterface $fileRepository) {
    $this->fileSystem = $fileSystem;
    $this->fileRepository = $fileRepository;
  }

  /**
   * Moves submitted file to destination
   *
   * @param int $fid
   *   Original file id
   * @param string $destination
   *   File destination folder path
   * @param string $fileName
   */
  public function transferSubmissionFile($fid, $destination, $fileName = NULL) {
    $original_file = File::load($fid);
    if (empty($fileName)) {
      $fileName = $original_file->getFileName();
    }
    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
    $this->fileRepository->copy($original_file, $destination . $fileName, FileSystemInterface::EXISTS_REPLACE);
  }

  /**
   * writes content to file
   *
   * @param string $content
   *    File contents to be written
   * @param string $destination
   *   File destination folder path
   * @param string $fileName
   *   name of file
   */
  public function writeContentToFile($content, $destination, $fileName) {
    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
    $this->fileRepository->writeData($content, $destination . $fileName, FileSystemInterface::EXISTS_REPLACE);
  }
}
