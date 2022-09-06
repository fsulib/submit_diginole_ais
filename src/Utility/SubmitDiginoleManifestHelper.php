<?php

namespace Drupal\submit_diginole_ais\Utility;

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
  public static function getCModel(string $submission_type) {
      $cModelCrosswalk = [
        "university_records_submission" => "",
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
        "minimal" => "",
        "other" => "",
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

  /**
   *
   * @param string $submission_type
   *
   * @return string
   *    Scholar Embargo DSID
   */
  public static function getScholarEmbargoDSID(string $submission_type) {
    $dsidCrosswalk = [
        "university_records_submission" => "",
        "honors_thesis_submission" => "PDF",
        "3d_object" => "OBJ",
        "audio" => "MP3",
        "book" => "PDF",
        "capstone_project" => "PDF",
        "conference" => "PDF",
        "data_set" => "OBJ",
        "doctoral_nursing_program_capstone_project" => "PDF",
        "editorial" => "PDF",
        "journal_article" => "PDF",
        "minimal" => "",
        "other" => "",
        "report" => "PDF",
        "review" => "PDF",
        "video" => "MP4",
        "working_paper" => "PDF",
      ];

     return $dsidCrosswalk[$submission_type];
  }

 }
