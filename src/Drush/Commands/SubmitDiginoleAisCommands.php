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
  #[CLI\Argument(name: 'submission_status', description: 'Status of submissions to process')]
  #[CLI\Option(name: 'dryrun', description: 'Run in dryrun mode where nothing is actually processed')]
  #[CLI\Usage(name: 'drush ais_process research_repository_submission approved', description: 'Processes approved submissions from the research_repository_submission webform')]
  public function process($webform_id, $submission_status, $options = ['dryrun' => FALSE]) {
    $message = "Processing {$submission_status} submissions from the {$webform_id} webform";
    if ($options['dryrun']) {
      $message .= " in dryrun mode";
    }
    $this->logger()->success($message);
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
