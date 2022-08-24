<?php
namespace Drupal\submit_diginole_ais\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\Messenger\Messenger;
use Drush\Commands\DrushCommands;
use Drupal\submit_diginole_ais\DiginoleSubmissionService;
use Drupal\submit_diginole_ais\Utility\SubmitDiginoleManifestHelper;
use Drupal\file\FileRepositoryInterface;

/**
 * A Drush commandfile for processing approved DigiNole submissions.
 *
 */
class ApprovedSubmissionCommands extends DrushCommands {
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
   * @var \Drupal\Core\Messsenger\Messenger
   */
  protected $messenger;

  /**
   * Twig Service
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twigService;

  /**
   * File repository interface
   *
   * @var \Drupal\file\FileRepositoryInterface;
   */
  protected $fileRepository;

  /**
   * File System Interface
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new ApprovedSubmissionCommands object.
   *
   * @param \Drupal\submit_diginole_ais\DiginoleSubmissionService $diginoleSubmissionService
   *  DigiNole Submission Service
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *  Entity type service
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *  Logger service
   * @param \Drupal\Core\Messsenger\Messenger $messenger
   *  Messenger Service
   * @param \Drupal\Core\Template\TwigEnvironment $twigService
   *  Twig Service
   * @param \Drupal\file\FileRepositoryInterface $fileRepository
   *  File repository service
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *  File system interface
   */
  public function __construct(
    DiginoleSubmissionService $diginoleSubmissionService,
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    Messenger $messenger,
    TwigEnvironment $twigService,
    FileRepositoryInterface $fileRepository,
    FileSystemInterface $fileSystem
    ) {
    $this->diginoleSubmissionService = $diginoleSubmissionService;
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->messenger = $messenger;
    $this->twigService = $twigService;
    $this->fileRepository = $fileRepository;
    $this->fileSystem = $fileSystem;
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

        $template = str_replace("_","-",$form_name) . '-mods.html.twig';
        $template_data = $this->diginoleSubmissionService->getTemplateData($submission);

        // $template_data contains each submission
        $rendered_output = $this->twigService->render('modules/custom/submit_diginole_ais/templates/' . $template, ['item' => $template_data]);

        $current_time = time();
        $filename = $form_name . '-' . $uuid . '-' . $current_time . '.xml';
        if ($this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY)) {
          $this->fileRepository->writeData($rendered_output, $path . $filename, FileSystemInterface::EXISTS_REPLACE);
          $this->loggerChannelFactory->get('ais_submissions')->info(dt('Saved file ' . $filename));
        }
        else {
          $this->loggerChannelFactory->get('ais_submissions')->error(dt('Unable to save file ' . $filename));
        }
      }

    }
  }

}
