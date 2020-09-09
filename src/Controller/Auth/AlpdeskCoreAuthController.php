<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Auth;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Alpdesk\AlpdeskCore\Library\Auth\AlpdeskCoreAuthToken;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreAuthException;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreAuthSuccessEvent;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreAuthVerifyEvent;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreAuthInvalidEvent;
use Alpdesk\AlpdeskCore\Library\Auth\AlpdeskCoreAuthResponse;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;
use Symfony\Component\Security\Core\User\UserInterface;

class AlpdeskCoreAuthController extends Controller {

  protected ContaoFramework $framework;
  protected AlpdeskCoreEventService $eventService;
  protected AlpdeskcoreLogger $logger;

  public function __construct(ContaoFramework $framework, AlpdeskCoreEventService $eventService, AlpdeskcoreLogger $logger) {
    $this->framework = $framework;
    $this->framework->initialize();
    $this->eventService = $eventService;
    $this->logger = $logger;
  }

  private function output(AlpdeskCoreAuthResponse $data, int $statusCode): JsonResponse {
    return ( new JsonResponse(array(
                'username' => $data->getUsername(),
                'alpdesk_token' => $data->getAlpdesk_token(),
                'verify' => $data->getVerify(),
                'invalid' => $data->getInvalid()
                    ), $statusCode
            ) );
  }

  private function outputError(string $data, int $statusCode): JsonResponse {
    return (new JsonResponse($data, $statusCode));
  }

  /**
   * 
   * @param Request $request
   * ENDPOINT: /auth
   * POST-JSON-PARAMS: {"username":"testmandant","password":"1234567890"}
   * 
   * @return JsonResponse
   * {"alpdesk_token":"JWT","username":"test"} with AlpdeskCoreConstants::$STATUSCODE_OK
   * OR ErrorMessage with AlpdeskCoreConstants::$STATUSCODE_COMMONERROR
   * 
   */
  public function auth(Request $request): JsonResponse {
    try {
      $authdata = (array) json_decode($request->getContent(), true);
      $response = (new AlpdeskCoreAuthToken())->generateToken($authdata);
      $event = new AlpdeskCoreAuthSuccessEvent($response);
      $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreAuthSuccessEvent::NAME);
      $this->logger->info('username:' . $event->getResultData()->getUsername() . ' | Auth successfully', __METHOD__);
      return $this->output($event->getResultData(), AlpdeskCoreConstants::$STATUSCODE_OK);
    } catch (AlpdeskCoreAuthException $exception) {
      $this->logger->error($exception->getMessage(), __METHOD__);
      return $this->outputError($exception->getMessage(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);
    }
  }

  /**
   * 
   * @param Request $request 
   * ENDPOINT: /auth/verify
   * Authorization Bearer TOKEN in Header
   * 
   * @return JsonResponse
   * {"username":"test","verify":true,"invalid":false} with AlpdeskCoreConstants::$STATUSCODE_OK
   * OR ErrorMessage with AlpdeskCoreConstants::$STATUSCODE_COMMONERROR
   * 
   */
  public function verify(Request $request, UserInterface $user): JsonResponse {
    try {
      $response = new AlpdeskCoreAuthResponse();
      $response->setUsername($user->getUsername());
      $response->setAlpdesk_token($user->getUsedToken());
      $response->setInvalid(false);
      $response->setVerify(true);
      $event = new AlpdeskCoreAuthVerifyEvent($response);
      $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreAuthVerifyEvent::NAME);
      $this->logger->info('username:' . $event->getResultData()->getUsername() . ' | Verify successfully', __METHOD__);
      return $this->output($event->getResultData(), AlpdeskCoreConstants::$STATUSCODE_OK);
    } catch (\Exception | AlpdeskCoreAuthException $exception) {
      $this->logger->error($exception->getMessage(), __METHOD__);
      return $this->outputError($exception->getMessage(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);
    }
  }

  /**
   * 
   * @param Request $request 
   * ENDPOINT: /auth/logout
   * 
   * @return JsonResponse
   * {"alpdesk_token":"JWT","username":"test","invalid":true,"verify":false} with AlpdeskCoreConstants::$STATUSCODE_OK
   * OR ErrorMessage with AlpdeskCoreConstants::$STATUSCODE_COMMONERROR
   * 
   */
  public function logout(Request $request, UserInterface $user): JsonResponse {
    try {
      $response = (new AlpdeskCoreAuthToken())->invalidToken($user);
      $event = new AlpdeskCoreAuthInvalidEvent($response);
      $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreAuthInvalidEvent::NAME);
      $this->logger->info('username:' . $event->getResultData()->getUsername() . ' | Logout successfully', __METHOD__);
      return $this->output($event->getResultData(), AlpdeskCoreConstants::$STATUSCODE_OK);
    } catch (\Exception | AlpdeskCoreAuthException $exception) {
      $this->logger->error($exception->getMessage(), __METHOD__);
      return $this->outputError($exception->getMessage(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);
    }
  }

}
