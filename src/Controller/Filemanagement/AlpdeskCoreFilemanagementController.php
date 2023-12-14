<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Filemanagement;

use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

class AlpdeskCoreFilemanagementController extends AbstractController
{
    protected string $rootDir;
    protected ContaoFramework $framework;
    protected AlpdeskCoreEventService $eventService;
    protected AlpdeskcoreLogger $logger;

    public function __construct(
        ContaoFramework         $framework,
        AlpdeskCoreEventService $eventService,
        AlpdeskcoreLogger       $logger,
        string                  $rootDir
    )
    {
        $this->framework = $framework;
        $this->eventService = $eventService;
        $this->logger = $logger;
        $this->rootDir = $rootDir;
    }

    /**
     * @param AlpdeskCoreFileuploadResponse $data
     * @param int $statusCode
     * @return JsonResponse
     */
    private function output(AlpdeskCoreFileuploadResponse $data, int $statusCode): JsonResponse
    {
        return (new JsonResponse(array(
            'username' => $data->getUsername(),
            'alpdesk_token' => $data->getAlpdesk_token(),
            'file' => $data->getFileName(),
            'uuid' => $data->getUuid(),
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
     * @param UserInterface $user
     * @return JsonResponse
     */
    public function upload(Request $request, UserInterface $user): JsonResponse
    {
        try {

            if (!($user instanceof AlpdeskcoreUser)) {
                throw new \Exception('invalid user type');
            }

            $this->framework->initialize();

            $uploadFile = $request->files->get('file');
            $target = $request->get('target');

            if ($uploadFile !== null && $target !== null) {

                $response = (new AlpdeskCoreFilemanagement($this->rootDir, $this->eventService))->upload($uploadFile, $target, $user);

                $event = new AlpdeskCoreFileuploadEvent($response);
                $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreFileuploadEvent::NAME);

                $this->logger->info('username:' . $event->getResultData()->getUsername() . ' | Upload successfully', __METHOD__);

                return $this->output($event->getResultData(), AlpdeskCoreConstants::$STATUSCODE_OK);
            }
            $this->logger->error('invalid parameters (=null) for upload', __METHOD__);
            return $this->outputError('invalid parameters (=null) for upload', AlpdeskCoreConstants::$ERROR_FILEMANAGEMENT_INVALIDFILES, AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);

        } catch (\Exception $exception) {

            $this->logger->error($exception->getMessage(), __METHOD__);
            return $this->outputError($exception->getMessage(), $exception->getCode(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);

        }
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @return BinaryFileResponse|JsonResponse
     */
    public function download(Request $request, UserInterface $user): BinaryFileResponse|JsonResponse
    {
        try {

            if (!($user instanceof AlpdeskcoreUser)) {
                throw new \Exception('invalid user type');
            }

            $this->framework->initialize();

            $downloadData = (array)\json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $file = (new AlpdeskCoreFilemanagement($this->rootDir, $this->eventService))->download($user, $downloadData);
            $this->logger->info('Download successfully', __METHOD__);

            return $file;

        } catch (\Exception|AlpdeskCoreFilemanagementException $exception) {

            $this->logger->error($exception->getMessage(), __METHOD__);
            return $this->outputError($exception->getMessage(), $exception->getCode(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);

        }
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @return JsonResponse
     */
    public function finder(Request $request, UserInterface $user): JsonResponse
    {
        try {

            if (!($user instanceof AlpdeskcoreUser)) {
                throw new \Exception('invalid user type');
            }

            $this->framework->initialize();

            $finderData = (array)\json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $response = (new AlpdeskCoreFilemanagement($this->rootDir, $this->eventService))->finder($user, $finderData);
            $this->logger->info('Finder successfully', __METHOD__);

            return (new JsonResponse($response, AlpdeskCoreConstants::$STATUSCODE_OK));

        } catch (\Exception|AlpdeskCoreFilemanagementException $exception) {

            $this->logger->error($exception->getMessage(), __METHOD__);
            return $this->outputError($exception->getMessage(), $exception->getCode(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);

        }
    }
}
