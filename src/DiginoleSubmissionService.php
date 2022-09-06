<?php

namespace Drupal\submit_diginole_ais;

use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform_query\WebformQuery;
use Drupal\submit_diginole_ais\Utility\SubmitDiginoleSubmissionHelper;

/**
 * Class DiginoleSubmissionService
 */
class DiginoleSubmissionService {

  /**
   * The Webform Query service
   * @var \Drupal\webform_query\WebformQuery
   */
  protected $webformQuery;

  /**
   * Constructs a new DiginoleSubmissionService object.
   * @param \Drupal\webform_query\WebformQuery $webform_query
   *    The webform query service

   */
  public function __construct(WebformQuery $webform_query) {
    $this->webformQuery = $webform_query;
  }

  public function getSidsByFormAndStatus(string $webform, string $status) {
    $query = $this->webformQuery;
    $query->setWebform($webform)
          ->addCondition('submission_status', $status);
    $results = $query->processQuery()->fetchCol();

    return $results;
  }

  public function getTemplateData(WebformSubmission $submission) {
    $submission_type = $submission->get('webform_id')->target_id;
    switch ($submission_type) {
      case 'honors_thesis_submission':
        return $this->getHonorsThesisData($submission);
        break;
      case 'research_repository_submission':
        return $this->getResearchRepositoryData($submission);
        break;
      case 'university_records_submission':
        return $this->getUniversityRecordsData($submission);
        break;
      default:
        return NULL;
        break;
    }
  }

  protected function getHonorsThesisData(WebformSubmission $submission) {
    $template_data = $this->getCommonData($submission);
    $submission_data = $submission->getData();

    $template_data['indentifier_doi'] = $submission_data['if_there_is_already_a_doi_associated_with_this_item_please_enter'];

    return $template_data;
  }

  protected function getResearchRepositoryData(WebformSubmission $submission) {
    $template_data = $this->getCommonData($submission);
    $submission_data = $submission->getData();

    $template_data['indentifier_doi'] = $submission_data['doi'];

    return $template_data;
  }

  protected function getUniversityRecordsData(WebformSubmission $submission) {
    return true;
  }

  protected function getCommonData(WebformSubmission $submission) {
    $template_data = [];
    $submission_data = $submission->getData();

    $template_data['titleInfo_title'] = $submission_data['submission_title'];
    $template_data['titleInfo_subTitle'] = $submission_data['submission_subtitle'];
    $template_data['abstract'] = $submission_data['abstract'];
    // need to iterate author
    foreach ($submission_data['author'] as $delta => $author) {
      $template_data['author'][$delta]['name_namePart_given'] = $author['author_first_name'] . ' ' . $author['author_middle_name'];
      $template_data['author'][$delta]['name_namePart_family'] = $author['author_last_name'];
      $template_data['author'][$delta]['name_affiliation'] = $author['author_institution'];
    }
    $template_data['note_keywords'] = $submission_data['keywords'];
    $template_data['originInfo_dateIssued'] = $submission_data['date_of_submission'];
    $template_data['note_publicationNote'] = $submission_data['publication_note'];
    $template_data['accessCondition_text'] = SubmitDiginoleSubmissionHelper::getLicenseLabel($submission_data['license']);
    $template_data['accessCondition_xlink'] = SubmitDiginoleSubmissionHelper::getLicenseUrl($submission_data['license']);

    return $template_data;
  }
}
