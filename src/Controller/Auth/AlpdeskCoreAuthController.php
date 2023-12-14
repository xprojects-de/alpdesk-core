<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Auth;

use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreAuthRefreshEvent;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
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
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AlpdeskCoreAuthController extends AbstractController
{
    protected ContaoFramework $framework;
    protected AlpdeskCoreEventService $eventService;
    protected AlpdeskcoreLogger $logger;
    protected PasswordHasherFactoryInterface $passwordHasherFactory;

    public function __construct(
        ContaoFramework                $framework,
        AlpdeskCoreEventService        $eventService,
        AlpdeskcoreLogger              $logger,
        PasswordHasherFactoryInterface $passwordHasherFactory
    )
    {
        $this->framework = $framework;

        $this->eventService = $eventService;
        $this->logger = $logger;
        $this->passwordHasherFactory = $passwordHasherFactory;
    }

    /**
     * @param AlpdeskCoreAuthResponse $data
     * @param int $statusCode
     * @return JsonResponse
     */
    private function output(AlpdeskCoreAuthResponse $data, int $statusCode): JsonResponse
    {
        return (new JsonResponse(array(
            'username' => $data->getUsername(),
            'alpdesk_token' => $data->getAlpdesk_token(),
            'verify' => $data->getVerify(),
            'invalid' => $data->getInvalid(),
            'expires' => ($data->getInvalid() === true ? 0 : $data->getExp())
        ), $statusCode
        ));
    }

    /**
     * @param string $data
     * @param mixed $code
     * @param int $statusCode
     * @return JsonResponse
     */
    private function outputError(string $data, mixed $code, int $statusCode): JsonResponse
    {
        if ($code === null || $code === 0 || $code === '') {
            $code = AlpdeskCoreConstants::$ERROR_COMMON;
        }

        return (new JsonResponse(['type' => $code, 'message' => $data], $statusCode));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function auth(Request $request): JsonResponse
    {
        try {

            $this->framework->initialize();

            // $request->getContent() must always be a valid JSON
            $authData = (array)\json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $response = (new AlpdeskCoreAuthToken($this->passwordHasherFactory))->generateToken($authData);

            $event = new AlpdeskCoreAuthSuccessEvent($response);
            $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreAuthSuccessEvent::NAME);
            $this->logger->info('username:' . $event->getResultData()->getUsername() . ' | Auth successfully', __METHOD__);

            return (new JsonResponse(array(
                'username' => $event->getResultData()->getUsername(),
                'alpdesk_token' => $event->getResultData()->getAlpdesk_token(),
                'alpdesk_refresh_token' => $event->getResultData()->getAlpdeskRefreshToken(),
                'verify' => $event->getResultData()->getVerify(),
                'invalid' => $event->getResultData()->getInvalid(),
                'expires' => ($event->getResultData()->getInvalid() === true ? 0 : $event->getResultData()->getExp())
            ), AlpdeskCoreConstants::$STATUSCODE_OK
            ));

        } catch (\Exception $exception) {

            $this->logger->error($exception->getMessage(), __METHOD__);
            return $this->outputError($exception->getMessage(), $exception->getCode(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);

        }
    }

    /**
     * @param UserInterface $user
     * @return JsonResponse
     */
    public function verify(UserInterface $user): JsonResponse
    {
        try {

            if (!($user instanceof AlpdeskcoreUser)) {
                throw new \Exception('invalid user type');
            }

            $this->framework->initialize();

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

    /**
     * @param Request $request
     * @param UserInterface $user
     * @return JsonResponse
     */
    public function refresh(Request $request, UserInterface $user): JsonResponse
    {
        try {

            if (!($user instanceof AlpdeskcoreUser)) {
                throw new \Exception('invalid user type');
            }

            $this->framework->initialize();

            // $request->getContent() must always be a valid JSON
            $refreshData = (array)\json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $response = (new AlpdeskCoreAuthToken($this->passwordHasherFactory))->refreshToken($refreshData, $user);

            $event = new AlpdeskCoreAuthRefreshEvent($response);
            $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreAuthRefreshEvent::NAME);
            $this->logger->info('username:' . $event->getResultData()->getUsername() . ' | Refresh successfully', __METHOD__);

            return (new JsonResponse(array(
                'alpdesk_token' => $event->getResultData()->getAlpdesk_token(),
                'alpdesk_refresh_token' => $event->getResultData()->getAlpdeskRefreshToken(),
                'expires' => ($event->getResultData()->getInvalid() === true ? 0 : $event->getResultData()->getExp())
            ), AlpdeskCoreConstants::$STATUSCODE_OK));

        } catch (\Exception $exception) {

            $this->logger->error($exception->getMessage(), __METHOD__);
            return $this->outputError($exception->getMessage(), $exception->getCode(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);

        }
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @return JsonResponse
     */
    public function member(Request $request, UserInterface $user): JsonResponse
    {
        try {

            if (!($user instanceof AlpdeskcoreUser)) {
                throw new \Exception('invalid user type');
            }

            $this->framework->initialize();

            $memberData = [];

            try {

                // Request could be empty
                $memberRequest = $request->getContent();
                if (\is_string($memberRequest) && $memberRequest !== '') {

                    $memberDataT = \json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
                    if (\is_array($memberDataT)) {
                        $memberData = $memberDataT;
                    }

                }

            } catch (\Exception) {
            }

            if (\array_key_exists('mandantid', $memberData) && $user->getIsAdmin() === true) {

                $mandantId = (string)AlpdeskcoreInputSecurity::secureValue($memberData['mandantid']);

                if ($mandantId !== "") {

                    if ($mandantId === "0") {

                        $memberObject = MemberModel::findByPk($user->getMemberId());
                        if ($memberObject !== null) {
                            $memberObject->alpdeskcore_mandant = 0;
                            $memberObject->save();
                            $user->setMandantPid(0);
                        }
                    } else {

                        if (!\array_key_exists((int)$mandantId, $user->getMandantWhitelist())) {
                            throw new AlpdeskCoreAuthException('mandantid not in whitelistarray', AlpdeskCoreConstants::$ERROR_INVALID_MANDANT);
                        }

                        $memberObject = MemberModel::findByPk($user->getMemberId());
                        if ($memberObject !== null) {
                            $memberObject->alpdeskcore_mandant = (int)$mandantId;
                            $memberObject->save();
                            $user->setMandantPid((int)$mandantId);
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

    /**
     * @param UserInterface $user
     * @return JsonResponse
     */
    public function logout(UserInterface $user): JsonResponse
    {
        try {

            if (!($user instanceof AlpdeskcoreUser)) {
                throw new \Exception('invalid user type');
            }

            $this->framework->initialize();

            $response = (new AlpdeskCoreAuthToken($this->passwordHasherFactory))->invalidToken($user);

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
