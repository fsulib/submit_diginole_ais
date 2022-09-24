<?php

namespace Drupal\submit_diginole_ais\Utility;

/**
 * Provides MODS helper functions
 */

 class SubmitDiginoleModsHelper {

  /**
   *
   * @param string $submission_type
   *
   * @return string
   *    CModel name
   */
  public static function getResourceTypes(string $submission_type) {
      $resourceTypeCrosswalk = [
        "university_records_submission" => array('mods' => 'mixed material', 'rda' => 'unspecified', 'coar' => array('id' => 'c_1843', 'label' => 'other')),
        "honors_thesis_submission" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_7a1f', 'label' => 'bachelor thesis')),
        "3d_object" => array('mods' => 'three dimensional object', 'rda' => 'three-dimensional form', 'coar' => array('id' => 'c_1843', 'label' => 'other')),
        "audio" => array('mods' => 'sound recording', 'rda' => 'sounds', 'coar' => array('id' => 'c_18cc', 'label' => 'sound')),
        "book" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_2f33', 'label' => 'book')),
        "book_chapter" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_3248', 'label' => 'book part')),
        "capstone_project" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_46ec', 'label' => 'thesis')),
        "conference" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_c94f', 'label' => 'conference output')),
        "conference_paper" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_5794', 'label' => 'conference paper')),
        "conference_poster" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_6670', 'label' => 'conference poster')),
        "conference_presentation" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'R60J-J5BD', 'label' => 'conference presentation')),
        "data_set" => array('mods' => 'mixed material', 'rda' => 'computer dataset', 'coar' => array('id' => 'c_ddb1', 'label' => 'dataset')),
        "doctoral_nursing_program_capstone_project" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_46ec', 'label' => 'thesis')),
        "editorial" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_b239', 'label' => 'editorial')),
        "journal_article" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_6501', 'label' => 'journal article')),
        "minimal" => array('mods' => 'text', 'rda' => 'unspecified', 'coar' => array('id' => 'c_1843', 'label' => 'other')),
        "other" => array('mods' => 'text', 'rda' => 'unspecified', 'coar' => array('id' => 'c_1843', 'label' => 'other')),
        "report" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_93fc', 'label' => 'report')),
        "policy" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_186u', 'label' => 'policy report')),
        "research" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_18ws', 'label' => 'research report')),
        "technical" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_18gh', 'label' => 'technical report')),
        "review" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_efa0', 'label' => 'review')),
        "book_review" array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_ba08', 'label' => 'book review')),
        "video" => array('mods' => 'moving image', 'rda' => 'two-dimensional moving image', 'coar' => array('id' => 'c_12ce', 'label' => 'video')),
        "working_paper" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_8042', 'label' => 'working paper')),
      ];

      return $resourceTypeCrosswalk[$submission_type];
    }

 }
