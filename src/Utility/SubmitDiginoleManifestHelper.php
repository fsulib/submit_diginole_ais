<?php

namespace Drupal\submit_diginole_ais\Utility;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Provides Manifest.ini helper functions
 */

 class SubmitDiginoleManifestHelper {

  /**
   *
   * @param string $submission_type
   *
   * @return string
   *    CModel name
   */
  public static function getCModel(string $submission_type, WebformSubmission $submission) {
      $cModelCrosswalk = [
        "university_records_submission" => "islandora:binaryObjectCModel",
        "honors_thesis_submission" => "ir:thesisCModel",
        "3d_object" => "islandora:binaryObjectCModel",
        "audio" => "islandora:sp-audioCModel",
        "book" => "ir:citationCModel",
        "book_chapter" => "ir:citationCModel",
        "capstone_project" => "ir:thesisCModel",
        "conference" => "ir:citationCModel",
        "conference_paper" => "ir:citationCModel",
        "conference_poster" => "ir:citationCModel",
        "conference_presentation" => "ir:citationCModel",
        "data_set" => "islandora:binaryObjectCModel",
        "doctoral_nursing_program_capstone_project" => "ir:thesisCModel",
        "editorial" => "ir:citationCModel",
        "journal_article" => "ir:citationCModel",
        "minimal" => "islandora:binaryObjectCModel",
        "other" => SubmitDiginoleManifestHelper::getOtherCModel($submission),
        "report" => "ir:citationCModel",
        "policy" => "ir:citationCModel",
        "research" => "ir:citationCModel",
        "technical" => "ir:citationCModel",
        "review" => "ir:citationCModel",
        "book_review" => "ir:citationCModel",
        "video" => "islandora:sp_videoCModel",
        "working_paper" => "ir:citationCModel",
      ];

      return $cModelCrosswalk[$submission_type];
    }


  public static function getOtherCModel(WebformSubmission $submission) {
    $webform = $submission->get('webform_id')->target_id;
    if ($webform == 'honors_thesis_submission') {
      $fid = $submission->getData()['upload_honors_thesis'][0];
    }
    elseif ($webform == 'research_repository_submission') {
      $fid = $submission->getData()['upload_element'][0];
    }
    $file = \Drupal\file\Entity\File::load($fid);
    $file_name = $file->get('filename')->value;
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $cModelCrosswalk = [
      "bmp" => "islandora:sp_basic_image",
      "gif" => "islandora:sp_basic_image",
      "jpeg" => "islandora:sp_basic_image",
      "jpg" => "islandora:sp_basic_image",
      "png" => "islandora:sp_basic_image",
      "tif" => "islandora:sp_large_image_cmodel",
      "mp3" => "islandora:sp-audioCModel",
      "ogg" => "islandora:sp-audioCModel",
      "wav" => "islandora:sp-audioCModel",
      "avi" => "islandora:sp_videocmodel",
      "mkv" => "islandora:sp_videocmodel",
      "mov" => "islandora:sp_videocmodel",
      "mp4" => "islandora:sp_videocmodel",
      "bin" => "islandora:binaryObjectCModel",
      "bz2" => "islandora:binaryObjectCModel",
      "csv" => "islandora:binaryObjectCModel",
      "dmg" => "islandora:binaryObjectCModel",
      "doc" => "islandora:binaryObjectCModel",
      "docx" => "islandora:binaryObjectCModel",
      "eps" => "islandora:binaryObjectCModel",
      "gz" => "islandora:binaryObjectCModel",
      "html" => "islandora:binaryObjectCModel",
      "jar" => "islandora:binaryObjectCModel",
      "odf" => "islandora:binaryObjectCModel",
      "pict" => "islandora:binaryObjectCModel",
      "ppt" => "islandora:binaryObjectCModel",
      "pptx" => "islandora:binaryObjectCModel",
      "psd" => "islandora:binaryObjectCModel",
      "rar" => "islandora:binaryObjectCModel",
      "rtf" => "islandora:binaryObjectCModel",
      "sit" => "islandora:binaryObjectCModel",
      "svg" => "islandora:binaryObjectCModel",
      "tar" => "islandora:binaryObjectCModel",
      "txt" => "islandora:binaryObjectCModel",
      "xls" => "islandora:binaryObjectCModel",
      "xlsx" => "islandora:binaryObjectCModel",
      "xml" => "islandora:binaryObjectCModel",
      "zip" => "islandora:binaryObjectCModel",
      "pdf" => "ir:citationCModel",
    ];

    return $cModelCrosswalk[$file_extension];
  }    

  /**
   *
   * @param string $submission_type
   *
   * @return string
   *    Scholar Embargo DSID
   */
  public static function getScholarEmbargoDSID(string $submission_type) {
    $dsidCrosswalk = [
        "university_records_submission" => "OBJ",
        "honors_thesis_submission" => "PDF",
        "3d_object" => "OBJ",
        "audio" => "MP3",
        "book" => "PDF",
        "book_chapter" => "PDF",
        "capstone_project" => "PDF",
        "conference" => "PDF",
        "conference_paper" => "PDF",
        "conference_poster" => "PDF",
        "conference_presentation" => "PDF",
        "data_set" => "OBJ",
        "doctoral_nursing_program_capstone_project" => "PDF",
        "editorial" => "PDF",
        "journal_article" => "PDF",
        "minimal" => "OBJ",
        "other" => "OBJ",
        "report" => "PDF",
        "policy" => "PDF",
        "research" => "PDF",
        "technical" => "PDF",
        "review" => "PDF",
        "book_review" => "PDF",
        "video" => "MP4",
        "working_paper" => "PDF",
      ];

     return $dsidCrosswalk[$submission_type];
  }

 }
