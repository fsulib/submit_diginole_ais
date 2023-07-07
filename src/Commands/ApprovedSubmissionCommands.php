<?php
namespace Drupal\submit_diginole_ais\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\Messenger\Messenger;
use Drush\Commands\DrushCommands;
use Drupal\webform\WebformSubmissionForm;
use Drupal\submit_diginole_ais\DiginoleSubmissionService;
use Drupal\submit_diginole_ais\SubmitDiginoleFileService;
use Drupal\submit_diginole_ais\SubmitDiginoleManifestService;


/**
 * A Drush commandfile for processing approved DigiNole submissions.
 *
 */
class ApprovedSubmissionCommands extends DrushCommands {

  const TEMPLATE_PATH = 'modules/custom/submit_diginole_ais/templates/';

  const BASE_PATH = 'temporary://ais_submissions/';

  /**
   * THe DigiNole Submission service.
   *
   * @var \Drupal\submit_diginole_ais\DiginoleSubmissionService
   */
  protected $diginoleSubmissionService;

  /**
   * Entity type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * Drupal messenger service
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Twig Service
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twigService;

  /**
   * The Submit DigiNole File service.
   *
   * @var \Drupal\submit_diginole_ais\SubmitDiginoleFileService
   */
  protected $submitDiginoleFileService;

  /**
   * The Submit DigiNole Manifest service.
   *
   * @var \Drupal\submit_diginole_ais\SubmitDiginoleManifestService
   */
  protected $submitDiginoleManifestService;

  /**
   * Constructs a new ApprovedSubmissionCommands object.
   *
   * @param \Drupal\submit_diginole_ais\DiginoleSubmissionService $diginoleSubmissionService
   *  DigiNole Submission Service
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *  Entity type service
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *  Logger service
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *  Messenger Service
   * @param \Drupal\Core\Template\TwigEnvironment $twigService
   *  Twig Service
   * @param \Drupal\submit_diginole_ais\SubmitDiginoleFileService $submitDiginoleFileService
   *  Submit DigiNole File Service
   * @param \Drupal\submit_diginole_ais\SubmitDiginoleManifestService $submitDiginoleManifestService
   *  Submit DigiNole Manifest Service
   */
  public function __construct(
    DiginoleSubmissionService $diginoleSubmissionService,
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    Messenger $messenger,
    TwigEnvironment $twigService,
    SubmitDiginoleFileService $submitDiginoleFileService,
    SubmitDiginoleManifestService $submitDiginoleManifestService
    ) {
    $this->diginoleSubmissionService = $diginoleSubmissionService;
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->messenger = $messenger;
    $this->twigService = $twigService;
    $this->submitDiginoleFileService = $submitDiginoleFileService;
    $this->submitDiginoleManifestService = $submitDiginoleManifestService;
  }

  /**
   * Purge old ingested DigiNole submissions
   *
   * @command submit_diginole_ais:purge_submissions
   * @aliases ais_purge
   *
   * @param string $webform
   *    The machine name of the webform providing submissions
   * @option dryrun
   *   Whether or not to run in dryrun mode, which shows how many submissions would be deleted but does not actually delete them 
   * 
   * @usage submit_diginole_ais:purge_submissions honors_thesis_submission --dryrun=true
   */
  public function purgeSubmissions($webform, $options = ['dryrun' => FALSE]) {
    $webformChoices = ["honors_thesis_submission","research_repository_submission","university_records_submission"];
    if (!in_array($webform, $webformChoices)) {
      $this->messenger->addError(dt('You passed an incorrect parameter value. Accepted values are: "honors_thesis_submission","research_repository_submission","university_records_submission"'));
    }
    else {
      if ($options['dryrun']) {
        $dryrun = TRUE;
        $this->messenger->addMessage("Running in 'dryrun' mode. Submissions will not actually be deleted.");
      }
      else {
        $dryrun = FALSE;
      }
      $this->messenger->addMessage("Beginning process of purging old ingested submissions in {$webform} webform.");
      $current_time = time();
      $purge_stats = array();
      $purge_stats['webform'] = $webform;
      $purge_stats['dryrun'] = ($dryrun) ? 'Yes' : 'No';
      $purge_stats['start'] = time();
      $purge_stats['fresh'] = 0;
      $purge_stats['stale'] = 0;

      $sids = $this->diginoleSubmissionService->getSidsByFormAndStatus($webform, 'ingested');
      $count = count($sids);
      $purge_stats['ingested'] = $count;
      $this->messenger->addMessage("{$count} ingested submissions detected for {$webform} webform.");
      
      if ($count > 0) {
        foreach ($sids as $sid) {
          $this->messenger->addMessage("Analyzing submission #{$sid}...");
          $submission = $this->entityTypeManager->getStorage('webform_submission')->load($sid);
          $changed_time = $submission->getChangedTime();
          $changed_date = date('Y-m-d', $changed_time); 
          $stale_time = $current_time - $changed_time; 
          $stale_days = intdiv($stale_time, 86400); 
          $this->messenger->addMessage("Submission #{$sid} last changed {$changed_date}, {$stale_days} days ago.");
          if ($stale_days > 60) {
            $purge_stats['stale']++;
            $this->messenger->addMessage("Submission #{$sid} over 60 days stale, and will be purged.");
            if (!$dryrun) {
              $submission->delete();
              $this->messenger->addMessage("Submission #{$sid} purged.");
            }
            else {
              $this->messenger->addMessage("Submission #{$sid} purged (jk lol, #dryrun)");
            }
          }
          else {
            $purge_stats['fresh']++;
            $this->messenger->addMessage("Submission #{$sid} is less than 60 days stale, and will not be purged.");
          }
        }
      }
      $purge_stats['end'] = time();
      $purge_stats['length'] = intdiv($purge_stats['end'] - $purge_stats['start'], 60);
      $purge_stats_msg = "submit_diginole_ais purge run log:<br>";
      $purge_stats_msg .= "Run Date: " . date("Y-m-d", $purge_stats['start']) . "<br>"; 
      $purge_stats_msg .= "Run Duration: " . $purge_stats['length'] . " minutes (" . $purge_stats['start'] . " - " . $purge_stats['end'] . ")<br>"; 
      $purge_stats_msg .= "Target Webform: {$purge_stats['webform']}<br>";
      $purge_stats_msg .= "Dryrun: {$purge_stats['dryrun']}<br>";
      $purge_stats_msg .= "Total ingested submissions: {$purge_stats['ingested']}<br>";
      $purge_stats_msg .= "Ingested submissions still fresh: {$purge_stats['fresh']}<br>";
      $purge_stats_msg .= "Stale ingested submissions purged: {$purge_stats['stale']}<br>";

      $this->messenger->addMessage($purge_stats_msg);
      \Drupal::logger('submit_diginole_ais_purge_log')->info($purge_stats_msg);
    }
  }


  /**
   * Process DigiNole submissions
   *
   * @command submit_diginole_ais:process_submissions
   * @aliases ais_process
   *
   * @param string $webform
   *    The machine name of the webform providing submissions
   * @option status
   *    The status of submissions to process, default is approved
   *
   * @usage submit_diginole_ais:process_submissions honors_thesis_submission --status=approved
   */
  public function processSubmissions($webform, $options = ['status' => 'approved']) {
    $run_stats = array();
    $run_stats['start'] = time();
    $run_stats['processed'] = array();
    $run_stats['succeeded'] = array();
    $run_stats['failed'] = array();
    $run_stats['packaged'] = array();
    $webformChoices = ["honors_thesis_submission","research_repository_submission","university_records_submission"];

    if (!in_array($webform, $webformChoices)) {
      $this->messenger->addError(dt('You passed an incorrect parameter value. Accepted values are: "honors_thesis_submission","research_repository_submission","university_records_submission"'));
    }
    else {
      shell_exec('rm -rf /tmp/ais_packages');
      shell_exec('mkdir /tmp/ais_packages');
      if ($options['status']) {
        $status = $options['status'];
      }
      $run_stats['webform'] = $webform;
      $run_stats['status'] = $status;
      $sids = $this->diginoleSubmissionService->getSidsByFormAndStatus($webform, $status);
      $count = count($sids);
      $run_stats['total'] = $count;
      if ($count > 0) {
        $this->messenger->addMessage('Begin processing '. count($sids) . ' submissions.');
      }
      else {
        $this->messenger->addMessage('No submissions found to process.');
      }
      foreach ($sids as $sid) {
        $submission = $this->entityTypeManager->getStorage('webform_submission')->load($sid);
        $iid = $this->diginoleSubmissionService->getIID($submission);
        $run_stats['processed'][] = "{$sid} / {$iid}";
        $this->messenger->addMessage("Processing {$iid}");

        $env = getenv('ENVIRONMENT');
	      switch ($env) {
          case 'prod':
	          $ais_env = 'prod';
	          $base_url = "https://diginole.lib.fsu.edu";
            $this->messenger->addMessage("Checking AIS-prod status of {$iid}.zip...");
            break;
          default:
	          $ais_env = 'test';
	          $base_url = "https://test.diginole.lib.fsu.edu";
            $this->messenger->addMessage("Checking AIS-test status of {$iid}.zip...");
            break;
        }
	      $ais_api_path = "/diginole/webservices/ais/package/status/";
	      $url = $base_url . $ais_api_path . $iid . ".zip";

	      if (!$url) {
          $ais_package_status = 'false';
        }
	      else {
          $ais_package_status = file_get_contents($url);
        }

	      $timestamp = date('Y-m-d', time());
        if ($ais_package_status != 'false' && $status != 'rerun') {
	        $response = json_decode($ais_package_status, TRUE);
	        if ($response['status'] == 'Success') {
            $run_stats['succeeded'][] = "{$sid} / {$iid}";
	          $submission_log_message = "Submission {$iid} has been ingested by AIS-{$ais_env} to create {$response['message']}."; 
            $this->messenger->addMessage($submission_log_message);
            $submission->setElementData('submission_status', 'ingested');
            $purlchecker_response = json_decode(file_get_contents($base_url . '/diginole/webservices/purlchecker/' . $iid), TRUE);
            $submission->setElementData('diginole_purl', $purlchecker_response['message']);
            $submission_log_message = "{$timestamp}: {$submission_log_message}";
          }
	        else {
            $run_stats['failed'][] = "{$sid} / {$iid}";
	          $submission_log_message = "Submission {$iid} has been ingested by AIS-{$ais_env} but encountered the following error: '{$response['message']}'."; 
            $this->messenger->addMessage($submission_log_message);
            $submission->setElementData('submission_status', 'error');
            $submission_log_message = "{$timestamp}: {$submission_log_message}";
	        }
          $submission_log = $submission->getElementData('submission_log');
          $submission_log[] = $submission_log_message;
          $submission->setElementData('submission_log', $submission_log);
          $submission->resave();
	        $submission = WebformSubmissionForm::submitWebformSubmission($submission);
          $this->messenger->addMessage("Skipping processing of {$iid}.");
        }
        else {
          $run_stats['packaged'][] = "{$sid} / {$iid}";
	        $submission_log_message = "Submission {$iid} has not been ingested by AIS-{$ais_env}, creating ingest package.";
          $this->messenger->addMessage($submission_log_message);
          $submission_log = $submission->getElementData('submission_log');
          $submission_log[] = "{$timestamp}: {$submission_log_message}";
          $submission->setElementData('submission_log', $submission_log);
          $submission->resave();
          $template = $this->diginoleSubmissionService->getSubmissionTemplate($submission);
          $template_data = $this->diginoleSubmissionService->getTemplateData($submission);

          // $template_data contains each submission
          $rendered_output = $this->twigService->render(self::TEMPLATE_PATH . $template, ['item' => $template_data]);

          $destination_folder = self::BASE_PATH . $iid . '/';
          $mods_filename = $iid . '.xml';
          $mods_file_result = $this->submitDiginoleFileService->writeContentToFile($rendered_output, $destination_folder, $mods_filename);

          if (empty($mods_file_result)) {
            $message = 'Saved file ' . $mods_filename;
            $this->loggerChannelFactory->get('ais_submissions')->info(dt($message));
            $this->messenger->addMessage($message);
          }
          else {
            $message = 'Unable to save file ' . $mods_filename;
            $this->messenger->addError($message);
          }

          // move files
          $fid = $this->diginoleSubmissionService->getSubmissionFID($submission);
          if (!empty($fid)) {
            $filename = $this->submitDiginoleFileService->transferSubmissionFile($fid, $destination_folder, $iid);
            $this->messenger->addMessage('Saved file ' . $filename);
            if (pathinfo("/tmp/ais_submissions/{$iid}/{$filename}", PATHINFO_EXTENSION) == 'pdf') {
              $this->submitDiginoleFileService->applyCoverpageToFile($iid, $filename, $submission->getData());
              $this->messenger->addMessage('Coverpaging ' . $filename);
            }
          }
          else {
            $message = 'Could not find attached file for ' . $iid;
            $this->messenger->addError($message);
          }

          // add manifest
          $manifest_template = $this->submitDiginoleManifestService->getManifestTemplate();
          $manifest_data = $this->submitDiginoleManifestService->getManifestContents($submission);
          $manifest_filename = $this->submitDiginoleManifestService->getManifestFilename();

          $rendered_manifest = $this->twigService->render(self::TEMPLATE_PATH . $manifest_template, ['manifest' => $manifest_data]);
          $manifest_file_result = $this->submitDiginoleFileService->writeContentToFile($rendered_manifest, $destination_folder, $manifest_filename);

          if (empty($manifest_file_result)) {
            $message = 'Saved file ' . $manifest_filename;
            $this->loggerChannelFactory->get('ais_submissions')->info(dt($message));
            $this->messenger->addMessage($message);
          }
          else {
            $message = 'Unable to save file ' . $manifest_filename;
            $this->loggerChannelFactory->get('ais_submissions')->error(dt($message));
            $this->messenger->addError($message);
          }

          // Create final AIS packages
          $this->messenger->addMessage('Packaging ' . $iid);
          shell_exec("cd /tmp/ais_submissions/{$iid}; zip /tmp/ais_packages/{$iid}.zip *");
          shell_exec('rm -rf /tmp/ais_submissions');
          $this->messenger->addMessage("Creation of {$iid}.zip AIS package complete.");
        }
      }
    }
    $run_stats['end'] = time();
    $run_stats['length'] = intdiv($run_stats['end'] - $run_stats['start'], 60);
    $run_stats_msg = "submit_diginole_ais processing run log:<br>";
    $run_stats_msg .= "Run Date: " . date("Y-m-d", $run_stats['start']) . "<br>"; 
    $run_stats_msg .= "Run Duration: " . $run_stats['length'] . " minutes (" . $run_stats['start'] . " - " . $run_stats['end'] . ")<br>"; 
    $run_stats_msg .= "Target Webform: {$run_stats['webform']}<br>";
    $run_stats_msg .= "Target Status: {$run_stats['status']}<br>";
    $run_stats_msg .= "<br>";

    
    if (!empty($run_stats['processed'])) {
      $run_stats_msg .= "Processed submissions:<br>";
      foreach ($run_stats['processed'] as $processed) {
        $run_stats_msg .= "- {$processed}<br>";
      }
    }
    else {
      $run_stats_msg .= "No processed submissions.<br>";
    }
    $run_stats_msg .= "<br>";


    if (!empty($run_stats['succeeded'])) {
      $run_stats_msg .= "Successful prior ingests:<br>";
      foreach ($run_stats['succeeded'] as $succeeded) {
        $run_stats_msg .= "- {$succeeded}<br>";
      }
    }
    else {
      $run_stats_msg .= "No successful prior ingests.<br>";
    }
    $run_stats_msg .= "<br>";

    if (!empty($run_stats['failed'])) {
      $run_stats_msg .= "Failed prior ingests:<br>";
      foreach ($run_stats['failed'] as $failed) {
        $run_stats_msg .= "- {$failed}<br>";
      }
    }
    else {
      $run_stats_msg .= "No failed prior ingests.<br>";
    }
    $run_stats_msg .= "<br>";

    if (!empty($run_stats['packaged'])) {
      $run_stats_msg .= "Packaged submissions:<br>";
      foreach ($run_stats['packaged'] as $packaged) {
        $run_stats_msg .= "- {$packaged}<br>";
      }
    }
    else {
      $run_stats_msg .= "No packaged submissions.<br>";
    }
    $run_stats_msg .= "<br>";

    $this->messenger->addMessage($run_stats_msg);
    \Drupal::logger('submit_diginole_ais_processing_log')->info($run_stats_msg);
  }

}
