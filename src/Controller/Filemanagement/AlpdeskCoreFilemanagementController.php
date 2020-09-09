<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Filemanagement;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Alpdesk\AlpdeskCore\Library\Filemanagement\AlpdeskCoreFilemanagement;
use Alpdesk\AlpdeskCore\Library\Filemanagement\AlpdeskCoreFileuploadResponse;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreFilemanagementException;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreFileuploadEvent;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;
use Symfony\Component\Security\Core\User\UserInterface;

class AlpdeskCoreFilemanagementController extends Controller {

  protected string $rootDir;
  protected ContaoFramework $framework;
  protected AlpdeskCoreEventService $eventService;
  protected AlpdeskcoreLogger $logger;

  public function __construct(ContaoFramework $framework, AlpdeskCoreEventService $eventService, AlpdeskcoreLogger $logger, string $rootDir) {
    $this->framework = $framework;
    $this->framework->initialize();
    $this->eventService = $eventService;
    $this->logger = $logger;
    $this->rootDir = $rootDir;
  }

  private function output(AlpdeskCoreFileuploadResponse $data, int $statusCode): JsonResponse {
    return ( new JsonResponse(array(
                'username' => $data->getUsername(),
                'alpdesk_token' => $data->getAlpdesk_token(),
                'file' => $data->getFileName(),
                    ), $statusCode
            ) );
  }

  private function outputError(string $data, int $statusCode): JsonResponse {
    return (new JsonResponse($data, $statusCode));
  }

  /**
   * 
   * @param Request $request
   * Formupload
   * 
   * @return JsonResponse
   * {"alpdesk_token":"alpdesk_test_1591436687_65108","username":"test","ttl":-3297} with AlpdeskCoreConstants::$STATUSCODE_OK
   * OR ErrorMessage with AlpdeskCoreConstants::$STATUSCODE_COMMONERROR
   * 
   */
  public function upload(Request $request, UserInterface $user): JsonResponse {
    try {
      $uploadFile = $request->files->get('file');
      $target = $request->get('target');
      if ($uploadFile !== null && $target !== null) {
        $response = (new AlpdeskCoreFilemanagement($this->rootDir))->upload($uploadFile, $target, $user);
        $event = new AlpdeskCoreFileuploadEvent($response);
        $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreFileuploadEvent::NAME);
        $this->logger->info('username:' . $event->getResultData()->getUsername() . ' | Upload successfully', __METHOD__);
        return $this->output($event->getResultData(), AlpdeskCoreConstants::$STATUSCODE_OK);
      }
      $this->logger->error('invalid parameters (=null) for upload', __METHOD__);
      return $this->outputError('invalid parameters (=null) for upload', AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);
    } catch (\Exception | AlpdeskCoreFilemanagementException $exception) {
      $this->logger->error($exception->getMessage(), __METHOD__);
      return $this->outputError($exception->getMessage(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);
    }
  }

  public function download(Request $request, UserInterface $user) {
    try {
      $downloaddata = (array) json_decode($request->getContent(), true);
      $file = (new AlpdeskCoreFilemanagement($this->rootDir))->download($user, $downloaddata);
      $this->logger->info('Download successfully', __METHOD__);
      return $file;
    } catch (\Exception | AlpdeskCoreFilemanagementException $exception) {
      $this->logger->error($exception->getMessage(), __METHOD__);
      return $this->outputError($exception->getMessage(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);
    }
  }

}
