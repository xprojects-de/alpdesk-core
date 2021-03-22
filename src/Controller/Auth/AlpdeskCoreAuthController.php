<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Auth;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Alpdesk\AlpdeskCore\Library\Auth\AlpdeskCoreAuthToken;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreAuthException;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreAuthSuccessEvent;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreAuthVerifyEvent;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreAuthInvalidEvent;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreAuthMemberEvent;
use Alpdesk\AlpdeskCore\Library\Auth\AlpdeskCoreAuthResponse;
use Alpdesk\AlpdeskCore\Library\Auth\AlpdeskCoreMemberResponse;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;
use Contao\MemberModel;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreInputSecurity;
use Symfony\Component\Security\Core\User\UserInterface;

class AlpdeskCoreAuthController extends AbstractController
{

    protected ContaoFramework $framework;
    protected AlpdeskCoreEventService $eventService;
    protected AlpdeskcoreLogger $logger;

    public function __construct(ContaoFramework $framework, AlpdeskCoreEventService $eventService, AlpdeskcoreLogger $logger)
    {
        $this->framework = $framework;
        $this->framework->initialize();
        $this->eventService = $eventService;
        $this->logger = $logger;
    }

    private function output(AlpdeskCoreAuthResponse $data, int $statusCode): JsonResponse
    {
        return (new JsonResponse(array(
            'username' => $data->getUsername(),
            'alpdesk_token' => $data->getAlpdesk_token(),
            'verify' => $data->getVerify(),
            'invalid' => $data->getInvalid(),
            'expires' => ($data->getInvalid() == true ? 0 : $data->getExp())
        ), $statusCode
        ));
    }

    private function outputError(string $data, $code, int $statusCode): JsonResponse
    {
        if ($code === null || $code === 0) {
            $code = AlpdeskCoreConstants::$ERROR_COMMON;
        }

        return (new JsonResponse(['type' => $code, 'message' => $data], $statusCode));
    }

    public function auth(Request $request): JsonResponse
    {
        try {

            $authdata = (array)\json_decode($request->getContent(), true);

            $response = (new AlpdeskCoreAuthToken())->generateToken($authdata);

            $event = new AlpdeskCoreAuthSuccessEvent($response);
            $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreAuthSuccessEvent::NAME);
            $this->logger->info('username:' . $event->getResultData()->getUsername() . ' | Auth successfully', __METHOD__);

            return $this->output($event->getResultData(), AlpdeskCoreConstants::$STATUSCODE_OK);

        } catch (\Exception $exception) {

            $this->logger->error($exception->getMessage(), __METHOD__);
            return $this->outputError($exception->getMessage(), $exception->getCode(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);

        }
    }

    public function verify(Request $request, UserInterface $user): JsonResponse
    {
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

        } catch (\Exception $exception) {

            $this->logger->error($exception->getMessage(), __METHOD__);
            return $this->outputError($exception->getMessage(), $exception->getCode(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);

        }
    }

    public function member(Request $request, UserInterface $user): JsonResponse
    {
        try {

            $memberdata = (array)json_decode($request->getContent(), true);

            if ($user->getIsAdmin() === true) {

                if (\array_key_exists('mandantid', $memberdata)) {

                    $mandantId = (string)AlpdeskcoreInputSecurity::secureValue($memberdata['mandantid']);

                    if ($mandantId !== "") {

                        if ($mandantId == "0") {

                            $memberObject = MemberModel::findByPk($user->getMemberId());
                            if ($memberObject !== null) {
                                $memberObject->alpdeskcore_mandant = 0;
                                $memberObject->save();
                                $user->setMandantPid(0);
                            }
                        } else {

                            if (!\array_key_exists($mandantId, $user->getMandantWhitelist())) {
                                throw new AlpdeskCoreAuthException('mandantid not in whitelistarray', AlpdeskCoreConstants::$ERROR_INVALID_MANDANT);
                            }

                            $memberObject = MemberModel::findByPk($user->getMemberId());
                            if ($memberObject !== null) {
                                $memberObject->alpdeskcore_mandant = \intval($mandantId);
                                $memberObject->save();
                                $user->setMandantPid(\intval($mandantId));
                            }
                        }
                    }
                }
            }

            $response = [
                'username' => $user->getUsername(),
                'alpdesk_token' => $user->getUsedToken(),
                'isadmin' => $user->getIsAdmin(),
                'memberid' => $user->getMemberId(),
                'mandantid' => $user->getMandantPid(),
                'mandantvalid' => ($user->getMandantPid() > 0),
                'mandantwhitelist' => $user->getMandantWhitelist()
            ];

            $responseObject = new AlpdeskCoreMemberResponse();
            $responseObject->setData($response);

            $event = new AlpdeskCoreAuthMemberEvent($responseObject);
            $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreAuthMemberEvent::NAME);

            return (new JsonResponse($event->getResultData()->getData(), AlpdeskCoreConstants::$STATUSCODE_OK));

        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), __METHOD__);
            return $this->outputError($exception->getMessage(), $exception->getCode(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);
        }
    }

    public function logout(Request $request, UserInterface $user): JsonResponse
    {
        try {

            $response = (new AlpdeskCoreAuthToken())->invalidToken($user);

            $event = new AlpdeskCoreAuthInvalidEvent($response);

            $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreAuthInvalidEvent::NAME);
            $this->logger->info('username:' . $event->getResultData()->getUsername() . ' | Logout successfully', __METHOD__);

            return $this->output($event->getResultData(), AlpdeskCoreConstants::$STATUSCODE_OK);

        } catch (\Exception $exception) {

            $this->logger->error($exception->getMessage(), __METHOD__);
            return $this->outputError($exception->getMessage(), $exception->getCode(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);

        }
    }

}
