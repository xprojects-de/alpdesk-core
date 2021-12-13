<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Plugin;

use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Alpdesk\AlpdeskCore\Library\Plugin\AlpdeskCorePlugin;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Library\Plugin\AlpdeskCorePlugincallResponse;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCorePlugincallEvent;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;
use Symfony\Component\Security\Core\User\UserInterface;

class AlpdeskCorePluginController extends AbstractController
{
    protected string $rootDir;
    protected ContaoFramework $framework;
    protected AlpdeskCoreEventService $eventService;
    protected AlpdeskcoreLogger $logger;

    public function __construct(ContaoFramework $framework, AlpdeskCoreEventService $eventService, AlpdeskcoreLogger $logger, string $rootDir)
    {
        $this->framework = $framework;

        $this->eventService = $eventService;
        $this->logger = $logger;
        $this->rootDir = $rootDir;
    }

    /**
     * @param AlpdeskCorePlugincallResponse $data
     * @param int $statusCode
     * @return JsonResponse
     */
    private function output(AlpdeskCorePlugincallResponse $data, int $statusCode): JsonResponse
    {
        return (new JsonResponse(array(
            'username' => $data->getUsername(),
            'alpdesk_token' => $data->getAlpdesk_token(),
            'plugin' => $data->getPlugin(),
            'data' => $data->getData(),
        ), $statusCode
        ));
    }

    /**
     * @param string $data
     * @param $code
     * @param int $statusCode
     * @return JsonResponse
     */
    private function outputError(string $data, $code, int $statusCode): JsonResponse
    {
        if ($code === null || $code === 0) {
            $code = AlpdeskCoreConstants::$ERROR_COMMON;
        }

        return (new JsonResponse(['type' => $code, 'message' => $data], $statusCode));
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @return JsonResponse
     */
    public function call(Request $request, UserInterface $user): JsonResponse
    {
        try {

            if (!($user instanceof AlpdeskcoreUser)) {
                throw new \Exception('invalid user type');
            }

            $this->framework->initialize();

            $plugindata = (array)\json_decode($request->getContent(), true);

            $response = (new AlpdeskCorePlugin($this->rootDir))->call($user, $plugindata);

            $event = new AlpdeskCorePlugincallEvent($response);
            $this->eventService->getDispatcher()->dispatch($event, AlpdeskCorePlugincallEvent::NAME);

            $this->logger->info('username:' . $event->getResultData()->getUsername() . ' | Plugincall "' . $event->getResultData()->getPlugin() . '" successfully', __METHOD__);

            return $this->output($event->getResultData(), AlpdeskCoreConstants::$STATUSCODE_OK);

        } catch (\Exception $exception) {

            $this->logger->error($exception->getMessage(), __METHOD__);
            return $this->outputError($exception->getMessage(), $exception->getCode(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);

        }
    }

}
