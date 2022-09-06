<?php

namespace Drupal\submit_diginole_ais;

use \DateTime;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\submit_diginole_ais\Utility\SubmitDiginoleManifestHelper;

class SubmitDiginoleManifestService {

  /**
   * Gets manifest filename
   */
  public function getManifestFilename() {
    return 'manifest.ini';
  }

  public function getManifestTemplate() {
    return 'manifest-ini.html.twig';
  }

  /**
   * Get manifest file
   */
  public function getManifestContents(WebformSubmission $submission) {
    // package information
    $submission_data = $submission->getData();
    $submission_form = $submission->get('webform_id')->target_id;
    if ($submission_form == 'research_repository_submission') {
      $submission_type = $submission_data['submission_type'];
    }
    else {
      $submission_type = $submission_form;
    }
    $submitter_email = $submission_data['email'];
    $content_model = SubmitDiginoleManifestHelper::getCModel($submission_type);
    $parent_collection = !empty($submission_data['diginole_collection']) ? $submission_data['diginole_collection'] : '';

    // ip_embargo
    if ($submission_data['visibility'] == 'viewable_on_campus') {
      $ip_embargo = 'indefinite';
    }
    else {
      $ip_embargo = '';
    }

    // scholar embargo
    if (!empty($submission_data['embargo_period'])) {
      if ($submission_data['embargo_period'] != 'unsure') {
        $selected_period = $submission_data['embargo_period'];
        $period_array = explode('_', $selected_period);
        $months = $period_array[0];
        $date_modifier = '+' . $months . ' month';

        $date = new DateTime('now');
        $date->modify($date_modifier);

        $scholar_expiry = $date->format('Y-m-d');
      }
      else {
        $scholar_expiry = 'indefinite';
      }

      $scholar_type = SubmitDiginoleManifestHelper::getScholarEmbargoDSID($submission_type);
    }
    else {
      $scholar_expiry = '';
      $scholar_type = '';
    }

    $manifest = [
      'submitter_email' => $submitter_email,
      'content_model' => $content_model,
      'parent_collection' => $parent_collection,
      'ip_embargo' => $ip_embargo,
      'scholar_expiry' => $scholar_expiry,
      'scholar_type' => $scholar_type,
    ];

    return $manifest;
  }

}
