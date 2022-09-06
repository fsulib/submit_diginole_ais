<?php
namespace Drupal\submit_diginole_ais\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\Messenger\Messenger;
use Drush\Commands\DrushCommands;
use Drupal\submit_diginole_ais\DiginoleSubmissionService;
use Drupal\submit_diginole_ais\SubmitDiginoleFileService;
use Drupal\submit_diginole_ais\SubmitDiginoleManifestService;


/**
 * A Drush commandfile for processing approved DigiNole submissions.
 *
 */
class ApprovedSubmissionCommands extends DrushCommands {

  const TEMPLATE_PATH = 'modules/custom/submit_diginole_ais/templates/';

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
    $webformChoices = ["honors_thesis_submission","research_repository_submission","university_records_submission"];

    if (!in_array($webform, $webformChoices)) {
      $this->messenger->addError(dt('You passed an incorrect parameter value. Accepted values are: "honors_thesis_submission","research_repository_submission","university_records_submission"'));
    }
    else {
      if ($options['status']) {
        $status = $options['status'];
      }
      // currently public for devlopment
      $path = "public://ais_submissions/";
      // $path = "temporary://ais_submissions/";
      $sids = $this->diginoleSubmissionService->getSidsByFormAndStatus($webform, $status);
      if (count($sids) > 0) {
        $this->messenger->addMessage('Begin processing '. count($sids) . ' submissions.');
      }
      else {
        $this->messenger->addMessage('No submissions found to process.');
      }
      foreach ($sids as $sid) {
        $submission = $this->entityTypeManager->getStorage('webform_submission')->load($sid);
        $form_name = $submission->get('webform_id')->target_id;
        $uuid = $submission->uuid();
        $iid = $form_name . '-' . $uuid;


        $template = str_replace("_","-",$form_name) . '-mods.html.twig';
        $template_data = $this->diginoleSubmissionService->getTemplateData($submission);

        // $template_data contains each submission
        $rendered_output = $this->twigService->render(self::TEMPLATE_PATH . $template, ['item' => $template_data]);

        $destination_folder = $path . $iid . '/';
        $mods_filename = $iid . '.xml';
        $mods_file_result = $this->submitDiginoleFileService->writeContentToFile($rendered_output, $destination_folder, $mods_filename);

        if (empty($mods_file_result)) {
          $message = 'Saved file ' . $mods_filename;
          $this->loggerChannelFactory->get('ais_submissions')->info(dt($message));
          $this->messenger->addMessage($message);
        }
        else {
          $message = 'Unable to save file ' . $mods_filename;
          $this->loggerChannelFactory->get('ais_submissions')->error(dt($message));
          $this->messenger->addError($message);
        }

        // move files
        if ($webform == 'honors_thesis_submission') {
          foreach ($submission->getData()['upload_honors_thesis'] as $fid) {
            $this->submitDiginoleFileService->transferSubmissionFile($fid, $destination_folder);
          }
        }

        // add manifest
        $manifest_template = $this->submitDiginoleManifestService->getManifestTemplate();
        $manifest_data = $this->submitDiginoleManifestService->getManifestContents($submission);
        $manifest_filename = $this->submitDiginoleManifestService->getManifestFilename();

        $rendered_manifest = $this->twigService->render(self::TEMPLATE_PATH . $manifest_template, ['manifest' => $manifest_data]);
        $manifest_file_result = $this->submitDiginoleFileService->writeContentToFile($rendered_manifest, $destination_folder, $manifest_filename);

        if (empty($manifest_file_result)) {
          $message = 'Saved file ' . $iid . '/' . $manifest_filename;
          $this->loggerChannelFactory->get('ais_submissions')->info(dt($message));
          $this->messenger->addMessage($message);
        }
        else {
          $message = 'Unable to save file ' . $iid . '/' . $manifest_filename;
          $this->loggerChannelFactory->get('ais_submissions')->error(dt($message));
          $this->messenger->addError($message);
        }
      }

    }
  }

}
