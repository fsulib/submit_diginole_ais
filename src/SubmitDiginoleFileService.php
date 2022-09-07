<?php

namespace Drupal\submit_diginole_ais;

use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\submit_diginole_ais\DiginoleSubmissionService;

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
   * THe DigiNole Submission service.
   *
   * @var \Drupal\submit_diginole_ais\DiginoleSubmissionService
   */
  protected $diginoleSubmissionService;

  /**
   * Constructs a new SubmitDiginoleFileService
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *  File system interface
   * @param \Drupal\file\FileRepositoryInterface $fileRepository
   *  File repository service
   * @param \Drupal\submit_diginole_ais\DiginoleSubmissionService $diginoleSubmissionService
   *  DigiNole Submission Service
   */
  public function __construct(FileSystemInterface $fileSystem, FileRepositoryInterface $fileRepository, DiginoleSubmissionService $diginoleSubmissionService) {
    $this->fileSystem = $fileSystem;
    $this->fileRepository = $fileRepository;
    $this->diginoleSubmissionService = $diginoleSubmissionService;
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
  public function transferSubmissionFile($fid, $destination, $iid) {
    $original_file = File::load($fid);
    $original_name = $original_file->getFileName();
    $original_name_parts = explode('.', $original_name);

    $new_name = $iid . '.' . $original_name_parts[1];

    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
    $this->fileRepository->copy($original_file, $destination . $new_name, FileSystemInterface::EXISTS_REPLACE);
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
