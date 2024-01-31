<?php

namespace Drupal\submit_diginole_ais\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class SubmitDiginoleAisCommands extends DrushCommands {

  /**
   * ais_process
   */
  #[CLI\Command(name: 'submit_diginole_ais:process_submissions', aliases: ['ais_process'])]
  #[CLI\Argument(name: 'webform_id', description: 'String ID of the webform to process from')]
  #[CLI\Option(name: 'dryrun', description: 'Run in dryrun mode where nothing is actually processed')]
  #[CLI\Usage(name: 'drush ais_process research_repository_submission', description: 'Processes submissions from the research_repository_submission webform')]
  public function process($webform_id, $options = ['dryrun' => FALSE]) {
    $webformChoices = ["honors_thesis_submission","research_repository_submission","university_records_submission"];
    if (!in_array($webform_id, $webformChoices)) {
      exit('Invalid webform_id supplied. Accepted values are "honors_thesis_submission","research_repository_submission","university_records_submission"');
    }
    $run_stats['start'] = time();
    $run_stats['processed'] = array();
    $run_stats['succeeded'] = array();
    $run_stats['failed'] = array();
    $run_stats['packaged'] = array();
    shell_exec('rm -rf /tmp/ais_packages');
    shell_exec('mkdir /tmp/ais_packages');
    $run_stats['webform'] = $webform_id;
    \Drupal::messenger()->addMessage("Gather approved and rerun ${webform_id} submissions.");
    $approved_sids = \Drupal::service('submit_diginole_ais.submission_service')->getSidsByFormAndStatus($webform_id, 'approved');
    $rerun_sids = \Drupal::service('submit_diginole_ais.submission_service')->getSidsByFormAndStatus($webform_id, 'rerun');
    $total_sids = array_merge($approved_sids, $rerun_sids);
    \Drupal::messenger()->addMessage(count($approved_sids) . " approved {$webform_id} submissions: [" . implode(', ', $approved_sids) . ']');
    \Drupal::messenger()->addMessage(count($rerun_sids) . " rerun {$webform_id} submissions: [" . implode(', ', $rerun_sids) . ']');
    \Drupal::messenger()->addMessage(count($total_sids) . " total {$webform_id} submissions: [" . implode(', ', $total_sids) . ']');

    $count = count($total_sids);
    $run_stats['total'] = $count;
    if ($count > 0) {
      \Drupal::messenger()->addMessage("Begin processing ${count} submissions.");
    }
    else {
      \Drupal::messenger()->addMessage('No submissions found to process.');
    }
    foreach ($total_sids as $sid) {
      $submission = \Drupal::entityTypeManager()->getStorage('webform_submission')->load($sid);
      $iid = \Drupal::service('submit_diginole_ais.submission_service')->getIID($submission);
      $run_stats['processed'][] = "{$sid} / {$iid}";
      \Drupal::messenger()->addMessage("Processing {$iid}");

      $env = getenv('ENVIRONMENT');
      switch ($env) {
        case 'prod':
          $ais_env = 'prod';
          $base_url = "https://diginole.lib.fsu.edu";
          \Drupal::messenger()->addMessage("Checking AIS-prod status of {$iid}.zip...");
          break;
        default:
          $ais_env = 'test';
          $base_url = "https://test.diginole.lib.fsu.edu";
          \Drupal::messenger()->addMessage("Checking AIS-test status of {$iid}.zip...");
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
          \Drupal::messenger()->addMessage($submission_log_message);
          $submission->setElementData('submission_status', 'ingested');
          $purlchecker_response = json_decode(file_get_contents($base_url . '/diginole/webservices/purlchecker/' . $iid), TRUE);
          $submission->setElementData('diginole_purl', $purlchecker_response['message']);
          $submission_log_message = "{$timestamp}: {$submission_log_message}";
        }
        else {
          $run_stats['failed'][] = "{$sid} / {$iid}";
          $submission_log_message = "Submission {$iid} has been ingested by AIS-{$ais_env} but encountered the following error: '{$response['message']}'."; 
          \Drupal::messenger()->addMessage($submission_log_message);
          $submission->setElementData('submission_status', 'error');
          $submission_log_message = "{$timestamp}: {$submission_log_message}";
        }
        $submission_log = $submission->getElementData('submission_log');
        $submission_log[] = $submission_log_message;
        $submission->setElementData('submission_log', $submission_log);
        $submission->resave();
        $submission = WebformSubmissionForm::submitWebformSubmission($submission);
        \Drupal::messenger()->addMessage("Skipping processing of {$iid}.");
      }
      else {
        $run_stats['packaged'][] = "{$sid} / {$iid}";
        $submission_log_message = "Submission {$iid} has not been ingested by AIS-{$ais_env}, creating ingest package.";
        \Drupal::messenger()->addMessage($submission_log_message);
        $submission_log = $submission->getElementData('submission_log');
        $submission_log[] = "{$timestamp}: {$submission_log_message}";
        $submission->setElementData('submission_log', $submission_log);
        $submission->resave();
        $template = \Drupal::service('submit_diginole_ais.submission_service')->getSubmissionTemplate($submission);
        $template_data = \Drupal::service('submit_diginole_ais.submission_service')->getTemplateData($submission);

        // $template_data contains each submission
        $rendered_output =  \Drupal::service('twig')->render('modules/custom/submit_diginole_ais/templates/' . $template, ['item' => $template_data]);

        $destination_folder = 'temporary://ais_submissions/' . $iid . '/';
        $mods_filename = $iid . '.xml';
        $mods_file_result = \Drupal::service('submit_diginole_ais.file_service')->writeContentToFile($rendered_output, $destination_folder, $mods_filename);

        if (empty($mods_file_result)) {
          $message = 'Saved file ' . $mods_filename;
          \Drupal::service('logger.factory')->get('ais_submissions')->info(dt($message));
          \Drupal::messenger()->addMessage($message);
        }
        else {
          $message = 'Unable to save file ' . $mods_filename;
          \Drupal::messenger()->addError($message);
        }

        // move files
        $fid = \Drupal::service('submit_diginole_ais.submission_service')->getSubmissionFID($submission);
        if (!empty($fid)) {
          $filename = \Drupal::service('submit_diginole_ais.file_service')->transferSubmissionFile($fid, $destination_folder, $iid);
          \Drupal::messenger()->addMessage('Saved file ' . $filename);
          if (pathinfo("/tmp/ais_submissions/{$iid}/{$filename}", PATHINFO_EXTENSION) == 'pdf') {
            \Drupal::service('submit_diginole_ais.file_service')->applyCoverpageToFile($iid, $filename, $submission->getData());
            \Drupal::messenger()->addMessage('Coverpaging ' . $filename);
          }
        }
        else {
          $message = 'Could not find attached file for ' . $iid;
          \Drupal::messenger()->addError($message);
        }

        // add manifest
        $manifest_template = \Drupal::service('submit_diginole_ais.manifest_service')->getManifestTemplate();
        $manifest_data = \Drupal::service('submit_diginole_ais.manifest_service')->getManifestContents($submission);
        $manifest_filename = \Drupal::service('submit_diginole_ais.manifest_service')->getManifestFilename();

        $rendered_manifest = \Drupal::service('twig')->render('modules/custom/submit_diginole_ais/templates/' . $manifest_template, ['manifest' => $manifest_data]);
        $manifest_file_result = \Drupal::service('submit_diginole_ais.file_service')->writeContentToFile($rendered_manifest, $destination_folder, $manifest_filename);

        if (empty($manifest_file_result)) {
          $message = 'Saved file ' . $manifest_filename;
          \Drupal::service('logger.factory')->get('ais_submissions')->info(dt($message));
          \Drupal::messenger()->addMessage($message);
        }
        else {
          $message = 'Unable to save file ' . $manifest_filename;
          \Drupal::service('logger.factory')->get('ais_submissions')->error(dt($message));
          \Drupal::messenger()->addError($message);
        }

        // Create final AIS packages
        \Drupal::messenger()->addMessage('Packaging ' . $iid);
        shell_exec("cd /tmp/ais_submissions/{$iid}; zip /tmp/ais_packages/{$iid}.zip *");
        shell_exec('rm -rf /tmp/ais_submissions');
        \Drupal::messenger()->addMessage("Creation of {$iid}.zip AIS package complete.");
      }
    }
    $run_stats['end'] = time();
    $run_stats['length'] = intdiv($run_stats['end'] - $run_stats['start'], 60);
    $run_stats_msg = "submit_diginole_ais processing run log:<br>";
    $run_stats_msg .= "Run Date: " . date("Y-m-d", $run_stats['start']) . "<br>"; 
    $run_stats_msg .= "Run Duration: " . $run_stats['length'] . " minutes (" . $run_stats['start'] . " - " . $run_stats['end'] . ")<br>"; 
    $run_stats_msg .= "Target Webform: {$run_stats['webform']}<br>";
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

    \Drupal::messenger()->addMessage($run_stats_msg);
    \Drupal::logger('submit_diginole_ais_processing_log')->info($run_stats_msg);




    #$message = "Processing {$submission_status} submissions from the {$webform_id} webform";
    #if ($options['dryrun']) {
    #  $message .= " in dryrun mode";
    #}
    #$this->logger()->success($message);
  }

  /**
   * ais_purge
   */
  #[CLI\Command(name: 'submit_diginole_ais:purge_submissions', aliases: ['ais_purge'])]
  #[CLI\Argument(name: 'webform_id', description: 'String ID of the webform to purge from')]
  #[CLI\Option(name: 'dryrun', description: 'Run in dryrun mode where nothing is actually purged')]
  public function purge($webform_id, $options = ['dryrun' => FALSE]) {
    $webformChoices = ["honors_thesis_submission","research_repository_submission","university_records_submission"];
    if (!in_array($webform_id, $webformChoices)) {
      \Drupal::messenger()->addError(dt('You passed an incorrect parameter value. Accepted values are: "honors_thesis_submission","research_repository_submission","university_records_submission"'));
    }
    else {
      if ($options['dryrun']) {
        $dryrun = TRUE;
        \Drupal::messenger()->addMessage("Running in 'dryrun' mode. Submissions will not actually be deleted.");
      }
      else {
        $dryrun = FALSE;
      }
      \Drupal::messenger()->addMessage("Beginning process of purging old ingested submissions in {$webform_id} webform.");
      $current_time = time();
      $purge_stats = array();
      $purge_stats['webform'] = $webform_id;
      $purge_stats['dryrun'] = ($dryrun) ? 'Yes' : 'No';
      $purge_stats['start'] = time();
      $purge_stats['fresh'] = 0;
      $purge_stats['stale'] = 0;
      $sids = \Drupal::service('submit_diginole_ais.submission_service')->getSidsByFormAndStatus($webform_id, 'ingested');
      $count = count($sids);
      $purge_stats['ingested'] = $count;
      \Drupal::messenger()->addMessage("{$count} ingested submissions detected for {$webform_id} webform.");
      if ($count > 0) {
        foreach ($sids as $sid) {
          \Drupal::messenger()->addMessage("Analyzing submission #{$sid}...");
          $submission = \Drupal::entityTypeManager()->getStorage('webform_submission')->load($sid);
          $changed_time = $submission->getChangedTime();
          $changed_date = date('Y-m-d', $changed_time); 
          $stale_time = $current_time - $changed_time; 
          $stale_days = intdiv($stale_time, 86400); 
          \Drupal::messenger()->addMessage("Submission #{$sid} last changed {$changed_date}, {$stale_days} days ago.");
          if ($stale_days > 0) {
            $purge_stats['stale']++;
            \Drupal::messenger()->addMessage("Submission #{$sid} over 60 days stale, and will be purged.");
            if (!$dryrun) {
              $submission->delete();
              \Drupal::messenger()->addMessage("Submission #{$sid} purged.");
            }
            else {
              \Drupal::messenger()->addMessage("Submission #{$sid} purged (jk lol, #dryrun)");
            }
          }
          else {
            $purge_stats['fresh']++;
            \Drupal::messenger()->addMessage("Submission #{$sid} is less than 60 days stale, and will not be purged.");
          }
        }
      }
    }
  }

  /**
   * ais_resave
   */
  #[CLI\Command(name: 'submit_diginole_ais:resave_submissions', aliases: ['ais_resave'])]
  #[CLI\Argument(name: 'webform_id', description: 'String ID of the webform to resave from')]
  #[CLI\Option(name: 'dryrun', description: 'Run in dryrun mode where nothing is actually resaved')]
  public function resave($webform_id, $options = ['dryrun' => FALSE]) {
    $message = "Resaving all submissions from the {$webform_id} webform";
    if ($options['dryrun']) {
      $message .= " in dryrun mode";
    }
    $this->logger()->success($message);
  }

}
