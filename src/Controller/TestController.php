<?php
namespace Drupal\submit_diginole_ais\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\submit_diginole_ais\DiginoleSubmissionService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TestController extends ControllerBase {
  protected $entityTypeManager;
  protected $diginoleSubmissionService;
  protected $twigService;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, DiginoleSubmissionService $diginoleSubmissionService, TwigEnvironment $twigService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->diginoleSubmissionService = $diginoleSubmissionService;
    $this->twigService = $twigService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('submit_diginole_ais.submission_service'),
      $container->get('twig')
    );
  }

  public function twigOutput() {
    $webform = 'honors_thesis_submission';
    $status = 'approved';
    $data = [];
    $title = 'Test Data';

    $sids = $this->diginoleSubmissionService->getSidsByFormAndStatus($webform, $status);

    foreach ($sids as $sid) {

      $submission = $this->entityTypeManager->getStorage('webform_submission')->load($sid);
      $form_name = $submission->get('webform_id')->target_id;
      $submission_data = $submission->getData();
      $submission_data['sid'] = $sid;
      $submission_data['form_name'] = $form_name;
      // $data contains each submission
      $data = $submission_data;
    }

    return [
      '#theme' => 'test_output',
      '#item' => $data,
    ];
  }

}
