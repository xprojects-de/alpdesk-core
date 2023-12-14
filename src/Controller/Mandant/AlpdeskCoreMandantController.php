<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Mandant;

use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdeskCoreMandant;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdeskCoreMandantResponse;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreMandantListEvent;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;
use Symfony\Component\Security\Core\User\UserInterface;

class AlpdeskCoreMandantController extends AbstractController
{
    protected ContaoFramework $framework;
    protected AlpdeskCoreEventService $eventService;
    protected AlpdeskcoreLogger $logger;

    public function __construct(
        ContaoFramework         $framework,
        AlpdeskCoreEventService $eventService,
        AlpdeskcoreLogger       $logger
    )
    {
        $this->framework = $framework;
        $this->eventService = $eventService;
        $this->logger = $logger;
    }

    /**
     * @param AlpdeskCoreMandantResponse $data
     * @param int $statusCode
     * @return JsonResponse
     */
    private function output(AlpdeskCoreMandantResponse $data, int $statusCode): JsonResponse
    {
        return (new JsonResponse(array(
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
     * @param UserInterface $user
     * @return JsonResponse
     */
    public function list(UserInterface $user): JsonResponse
    {
        try {

            if (!($user instanceof AlpdeskcoreUser)) {
                throw new \Exception('invalid user type');
            }

            $this->framework->initialize();

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

    /**
     * @param UserInterface $user
     * @return JsonResponse
     * @throws \Exception
     */
    public function edit(UserInterface $user): JsonResponse
    {
        if (!($user instanceof AlpdeskcoreUser)) {
            throw new \Exception('invalid user type');
        }

        $this->framework->initialize();

        return $this->outputError('Not Supported', AlpdeskCoreConstants::$ERROR_COMMON, AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);
    }

}
