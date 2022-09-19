<?php

namespace Drupal\submit_diginole_ais;

use Drupal\file\Entity\File;
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

  public function getIID(WebformSubmission $submission) {
    $form_name = $submission->get('webform_id')->target_id;
    $uuid = $submission->uuid();
    $iid = $form_name . '-' . $uuid;

    return $iid;
  }

  public function getSubmissionTemplate(WebformSubmission $submission) {
    $form_name = $submission->get('webform_id')->target_id;
    $template = str_replace("_","-",$form_name) . '-mods.html.twig';

    return $template;
  }

  public function getSubmissionFID(WebformSubmission $submission) {
    $fid = null;
    $webform = $submission->get('webform_id')->target_id;
    if ($webform == 'honors_thesis_submission') {
      $fid = $submission->getData()['upload_honors_thesis'][0];
    }
    elseif ($webform == 'research_repository_submission') {
      $fid = $submission->getData()['upload_element'][0];
    }
    return $fid;
  }

  public function getMimeTypeFromFID($fid) {
    $file = File::load($fid);
    $filename = $file->getFileUri();
    $mime_type = mime_content_type($filename);

    return $mime_type;
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
    $fid = $submission_data['upload_honors_thesis'][0];

    $template_data['indentifier_doi'] = $submission_data['if_there_is_already_a_doi_associated_with_this_item_please_enter'];
    $template_data['internetMediaType'] = $this->getMimeTypeFromFID($fid);

    return $template_data;
  }

  protected function getResearchRepositoryData(WebformSubmission $submission) {
    $template_data = $this->getCommonData($submission);
    $submission_data = $submission->getData();

    $fid = $submission_data['upload_element'][0];
    $template_data['internetMediaType'] = $this->getMimeTypeFromFID($fid);

    $template_data['indentifier_doi'] = array_key_exists('doi', $submission_data) ? $submission_data['doi'] : $submission_data['if_there_is_already_a_doi_associated_with_this_item_please_enter'];
    if (array_key_exists('date_of_publication', $submission_data)) {
      $template_data['originInfo_dateIssued'] = $submission_data['date_of_publication'];
    }
    if (array_key_exists('journal_of_publication', $submission_data)) {
      $template_data['publication_title'] = $submission_data['journal_of_publication'];
    }
    if (array_key_exists('publication_title', $submission_data)) {
      $template_data['publication_title'] = $submission_data['publication_title'];
    }
    if (array_key_exists('publisher_name', $submission_data)) {
      $template_data['publisher_name'] = $submission_data['publisher_name'];
    }
    if (array_key_exists('publication_edition', $submission_data)) {
      $template_data['publication_edition'] = $submission_data['publication_edition'];
    }
    if (array_key_exists('publication_volume', $submission_data)) {
      $template_data['publication_volume'] = $submission_data['publication_volume'];
    }
    if (array_key_exists('publication_issue', $submission_data)) {
      $template_data['publication_issue'] = $submission_data['publication_issue'];
    }
    if (array_key_exists('publication_page_range', $submission_data)) {
      if (strpos($submission_data['publication_page_range'], '-')) {
        $range_array = explode('-', $submission_data['publication_page_range']);
        $template_data['publication_page_range_start'] = $range_array[0];
        $template_data['publication_page_range_end'] = $range_array[1];
      }
      else {
        $template_data['publication_page_range_start'] = $submission_data['publication_page_range'];
      }
    }
    if (array_key_exists('isbn', $submission_data)) {
      $template_data['isbn'] = $submission_data['isbn'];
    }
    if (array_key_exists('preferred_citation', $submission_data)) {
      $template_data['preferred_citation'] = $submission_data['preferred_citation'];
    }
    /*if (array_key_exists('SOME_KEY', $submission_data)) {
      $template_data['SOME_KEY'] = $submission_data['SOME_KEY'];
    }*/

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
      $template_data['author'][$delta]['name_nameIdentifier_orcid'] = $author['author_orcid'];
      $template_data['author'][$delta]['name_affiliation'] = $author['author_institution'];
    }
    $template_data['note_keywords'] = $submission_data['keywords'];
    $template_data['originInfo_dateIssued'] = $submission_data['date_of_submission'];
    $template_data['note_publicationNote'] = $submission_data['publication_note'];
    $template_data['accessCondition_text'] = SubmitDiginoleSubmissionHelper::getLicenseLabel($submission_data['license']);
    $template_data['accessCondition_xlink'] = SubmitDiginoleSubmissionHelper::getLicenseUrl($submission_data['license']);

    $template_data['location_purl'] = $submission_data['diginole_purl'];
    $template_data['identifier_iid'] = $this->getIID($submission);
    $template_data['note_grantNumber'] = $submission_data['grant_number'];

    return $template_data;
  }
}
