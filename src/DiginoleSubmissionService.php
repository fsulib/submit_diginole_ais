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
    if ($fid) {
      $file = File::load($fid);
      $filename = $file->getFileUri();
      $mime_type = mime_content_type($filename);
      return $mime_type;
    }
    else {
      return FALSE;
    }
  }

  public function is3dObjectSubmission(WebformSubmission $submission) {
    $result = $submission->getElementData('form_type') == '3d_object' ? true : false;

    return $result;
  }

  public function get3dAdditionalUploads(WebformSubmission $submission) {
    $additional = [];
    $submission_data = $submission->getData();
    if (array_key_exists('material_s_upload')) {
      if (!empty($submission_data['material_s_upload'])) {
        $additional[] = $submission_data['material_s_upload'][0];
      }
    }
    if (array_key_exists('texture_upload')) {
      if (!empty($submission_data['texture_upload'])) {
        $additional[] = $submission_data['texture_upload'][0];
      }
    }
    return $additional;
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
    $template_data['identifier_doi'] = $submission_data['if_there_is_already_a_doi_associated_with_this_item_please_enter'];
    $template_data['identifier_doi'] = $this->formatDoi($template_data['identifier_doi']);
    $template_data['internetMediaType'] = $this->getMimeTypeFromFID($fid);
    return $template_data;
  }

  protected function getResearchRepositoryData(WebformSubmission $submission) {
    $template_data = $this->getCommonData($submission);
    $submission_data = $submission->getData();

    $fid = $submission_data['upload_element'][0];
    $template_data['internetMediaType'] = $this->getMimeTypeFromFID($fid);

    $template_data['identifier_doi'] = array_key_exists('doi', $submission_data) ? $submission_data['doi'] : $submission_data['if_there_is_already_a_doi_associated_with_this_item_please_enter'];
    $template_data['identifier_doi'] = $this->formatDoi($template_data['identifier_doi']);
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
    if (array_key_exists('units_of_scale', $submission_data)) {
      $template_data['units_of_scale'] = $submission_data['units_of_scale'];
    }
    if (array_key_exists('isbn', $submission_data)) {
      $template_data['isbn'] = $submission_data['isbn'];
    }
    if (array_key_exists('preferred_citation', $submission_data)) {
      $template_data['preferred_citation'] = $submission_data['preferred_citation'];
    }
    if (array_key_exists('method_of_creation', $submission_data)) {
      $template_data['method_of_creation'] = $submission_data['method_of_creation'];
    }
    if (array_key_exists('3d_model_base_unit', $submission_data)) {
      $template_data['three_d_model_base_unit'] = $submission_data['3d_model_base_unit'];
    }
    if (array_key_exists('extent', $submission_data)) {
      $template_data['extent'] = $submission_data['extent'];
    }
    if (array_key_exists('animated', $submission_data)) {
      $template_data['animated'] = $submission_data['animated'];
    }
    if (array_key_exists('rigged_geometries', $submission_data)) {
      $template_data['rigged_geometries'] = $submission_data['rigged_geometries'];
    }
    /*if (array_key_exists('SOME_KEY', $submission_data)) {
      $template_data['SOME_KEY'] = $submission_data['SOME_KEY'];
    }*/

    return $template_data;
  }

  protected function getUniversityRecordsData(WebformSubmission $submission) {
    return true;
  }

  protected function getSubmissionType(WebformSubmission $submission) {
    $submission_data = $submission->getData();
    $submission_form = $submission->get('webform_id')->target_id;
    if ($submission_form == 'research_repository_submission') {
      $submission_type = $submission_data['submission_type'];
    }
    else {
      $submission_type = $submission_form;
    }
    return $submission_type;
  }

  protected function getResourceTypes(WebformSubmission $submission) {
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
      "book_review" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_ba08', 'label' => 'book review')),
      "video" => array('mods' => 'moving image', 'rda' => 'two-dimensional moving image', 'coar' => array('id' => 'c_12ce', 'label' => 'video')),
      "working_paper" => array('mods' => 'text', 'rda' => 'text', 'coar' => array('id' => 'c_8042', 'label' => 'working paper')),
    ];

    $submission_type = $this->getSubmissionType($submission);
    return $resourceTypeCrosswalk[$submission_type];
  }

  protected function getCommonData(WebformSubmission $submission) {
    $template_data = [];
    $submission_data = $submission->getData();

    $exploded_title = explode(' ', $submission_data['submission_title']);
    $nonsorts = ['The', 'A', 'An', '...'];
    if (!in_array($exploded_title[0], $nonsorts)) {
      $template_data['titleInfo_title'] = $submission_data['submission_title'];
    }
    else {
      $template_data['titleInfo_nonSort'] = $exploded_title[0];
      $template_data['titleInfo_title'] = implode(' ', array_slice($exploded_title, 1));
    }
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

    $resource_data = $this->getResourceTypes($submission);
    $template_data['mods_resource'] = $resource_data['mods'];
    $template_data['rda_resource'] = $resource_data['rda'];
    $template_data['coar_resource_id'] = $resource_data['coar']['id'];
    $template_data['coar_resource_label'] = $resource_data['coar']['label'];

    return $template_data;
  }

  protected function formatDoi($doi) {
    $doi = str_replace('DOI: ','', $doi);
    $doi = str_replace('https://doi.org/','', $doi);
    return $doi;
  }
}
