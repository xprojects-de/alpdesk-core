<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Mandant;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdeskCoreMandant;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdeskCoreMandantResponse;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreMandantListEvent;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;
use Symfony\Component\Security\Core\User\UserInterface;

class AlpdeskCoreMandantController extends AbstractController {

  protected ContaoFramework $framework;
  protected AlpdeskCoreEventService $eventService;
  protected AlpdeskcoreLogger $logger;

  public function __construct(ContaoFramework $framework, AlpdeskCoreEventService $eventService, AlpdeskcoreLogger $logger) {
    $this->framework = $framework;
    $this->framework->initialize();
    $this->eventService = $eventService;
    $this->logger = $logger;
  }

  private function output(AlpdeskCoreMandantResponse $data, int $statusCode): JsonResponse {
    return ( new JsonResponse(array(
                'username' => $data->getUsername(),
                'alpdesk_token' => $data->getAlpdesk_token(),
                'mandantId' => $data->getMandantId(),
                'memberId' => $data->getMemberId(),
                'memberFirstname' => $data->getFirstname(),
                'memberLastname' => $data->getLastname(),
                'memberEmail' => $data->getEmail(),
                'accessFinderDownload' => $data->getAccessDownload(),
                'accessFinderUpload' => $data->getAccessUpload(),
                'accessFinderCreate' => $data->getAccessCreate(),
                'accessFinderDelete' => $data->getAccessDelete(),
                'accessFinderRename' => $data->getAccessRename(),
                'accessFinderMove' => $data->getAccessMove(),
                'accessFinderCopy' => $data->getAccessCopy(),
                'plugins' => $data->getPlugins(),
                'data' => $data->getData(),
                    ), $statusCode
            ) );
  }

  private function outputError(string $data, $code, int $statusCode): JsonResponse {

    if ($code === null || $code === 0) {
      $code = AlpdeskCoreConstants::$ERROR_COMMON;
    }

    return (new JsonResponse(['type' => $code, 'message' => $data], $statusCode));
  }

  public function list(Request $request, UserInterface $user): JsonResponse {
    try {
      $response = (new AlpdeskCoreMandant($this->eventService))->list($user);
      $event = new AlpdeskCoreMandantListEvent($response);
      $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreMandantListEvent::NAME);
      $this->logger->info('username:' . $event->getResultData()->getUsername() . ' | MandantList successfully', __METHOD__);
      return $this->output($event->getResultData(), AlpdeskCoreConstants::$STATUSCODE_OK);
    } catch (\Exception $exception) {
      $this->logger->error($exception->getMessage(), __METHOD__);
      return $this->outputError($exception->getMessage(), $exception->getCode(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);
    }
  }

  public function edit(Request $request, UserInterface $user): JsonResponse {
    return $this->outputError('Not Supported', AlpdeskCoreConstants::$ERROR_COMMON, AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);
  }

}
