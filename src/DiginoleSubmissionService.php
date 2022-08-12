<?php

namespace Drupal\submit_diginole_ais;

use Drupal\webform_query\WebformQuery;

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
}
