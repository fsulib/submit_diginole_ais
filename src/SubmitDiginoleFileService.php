<?php

namespace Drupal\submit_diginole_ais;

use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\submit_diginole_ais\DiginoleSubmissionService;
use setasign\Fpdi\Fpdi;

require_once(__DIR__ . '/../assets/fpdf.class.php');

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

    $new_name = $iid . '.' . end($original_name_parts);

    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
    $this->fileRepository->copy($original_file, $destination . $new_name, FileSystemInterface::EXISTS_REPLACE);
    return $new_name;
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

  /**
   * Applies coverpage to PDF file
   *
   * @param string $iid
   *   IID of submission
   * @param string $filename
   *   Name of file in package 
   * @param array $submission_data
   *   Data from submission entity 
   */
  public function applyCoverpageToFile($iid, $filename, $submission_data) {
    $coverpage_formatted_title = (!empty($submission_data['submission_subtitle']) ? trim(trim($submission_data['submission_title']), ':') . ": " . $submission_data['submission_subtitle'] : $submission_data['submission_title']);
    $coverpage_date = (!empty($submission_data['date_of_publication']) ? $submission_data['date_of_publication'] : $submission_data['date_of_submission']);
    $coverpage_formatted_year = date('Y', strtotime($coverpage_date));
    $coverpage_formatted_author_names = array();
    foreach ($submission_data['author'] as $author) {
	    if (!empty($author['author_middle_name'])) {
        $coverpage_formatted_author_name = "{$author['author_first_name']} {$author['author_middle_name']} {$author['author_last_name']}";
      }
      else {
        $coverpage_formatted_author_name = "{$author['author_first_name']} {$author['author_last_name']}";
      }
      $coverpage_formatted_author_names[] = $coverpage_formatted_author_name;
    }
    if (count($coverpage_formatted_author_names) == 1) {
      $coverpage_formatted_authors_string = implode("", $coverpage_formatted_author_names);
    }
    elseif (count($coverpage_formatted_author_names) == 2) {
      $coverpage_formatted_authors_string = implode(" and ", $coverpage_formatted_author_names);
    }
    else {
      $coverpage_formatted_authors_string = implode(", ", array_slice($coverpage_formatted_author_names, 0, -2)) . ", " . implode(" and ", array_slice($coverpage_formatted_author_names, -2)); 
    }
    $coverpage_generated_pdf = new Fpdi();
    $coverpage_generated_pdf->AddPage('P', 'Letter');
    $coverpage_generated_pdf->setSourceFile(__DIR__ . '/../assets/coverpage.pdf');
    $tplIdx = $coverpage_generated_pdf->importPage(1);
    $coverpage_generated_pdf->useTemplate($tplIdx);
    $coverpage_generated_pdf->SetTextColor(0, 0, 0);
    $coverpage_generated_pdf->SetFont('Times');
    $coverpage_generated_pdf->AddFont('DejaVuSerif', '', 'DejaVuSerif.ttf', true);
    $coverpage_generated_pdf->SetFont('DejaVuSerif', '');
    $coverpage_generated_pdf->setFontSize(14);
    $coverpage_generated_pdf->SetXY(25, 55);
    $coverpage_generated_pdf->Write(0, $coverpage_formatted_year);
    $coverpage_generated_pdf->setFontSize(26);
    $coverpage_generated_pdf->SetXY(25, 60);
    $coverpage_generated_pdf->MultiCell(0, 10, $coverpage_formatted_title, 0, 'L');
    $coverpage_generated_pdf->setFontSize(14);
    $coverpage_generated_pdf->setLeftMargin(25);
    $coverpage_generated_pdf->SetY($coverpage_generated_pdf->GetY() + 3);
    $coverpage_generated_pdf->MultiCell(0, 5, $coverpage_formatted_authors_string, 0, 'L');
    $coverpage_generated_pdf->setFontSize(8);
    $coverpage_generated_pdf->SetY($coverpage_generated_pdf->GetY() + 5);
    $coverpage_generated_pdf->MultiCell(0, 5, $submission_data['publication_note'], 0, 'L');
    $tmp_submissions_iid_path = "/tmp/ais_submissions/{$iid}";
    $coverpage_generated_pdf->Output("{$tmp_submissions_iid_path}/coverpage.pdf", 'F');
    shell_exec("mv {$tmp_submissions_iid_path}/{$iid}.pdf {$tmp_submissions_iid_path}/original.pdf");
    shell_exec("gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress -sOutputFile={$tmp_submissions_iid_path}/{$iid}.pdf {$tmp_submissions_iid_path}/coverpage.pdf {$tmp_submissions_iid_path}/original.pdf");
    shell_exec("rm {$tmp_submissions_iid_path}/coverpage.pdf {$tmp_submissions_iid_path}/original.pdf");
  }
}
