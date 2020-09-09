<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Plugin;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Alpdesk\AlpdeskCore\Library\Plugin\AlpdeskCorePlugin;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCorePluginException;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Library\Plugin\AlpdeskCorePlugincallResponse;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCorePlugincallEvent;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;
use Symfony\Component\Security\Core\User\UserInterface;

class AlpdeskCorePluginController extends Controller {

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

  private function output(AlpdeskCorePlugincallResponse $data, int $statusCode): JsonResponse {
    return ( new JsonResponse(array(
                'username' => $data->getUsername(),
                'alpdesk_token' => $data->getAlpdesk_token(),
                'plugin' => $data->getPlugin(),
                'data' => $data->getData(),
                    ), $statusCode
            ) );
  }

  private function outputError(string $data, int $statusCode): JsonResponse {
    return (new JsonResponse($data, $statusCode));
  }

  /**
   * 
   * @param Request $request 
   * ENDPOINT: /plugin
   * Authorization Bearer TOKEN in Header
   * POST-JSON-PARAMS: {"plugin":"hello","data":[]}
   * 
   * @return JsonResponse
   * {"username":"test","alpdesk_token":"alpdesk_test_1591436687_651089","plugin":"hello","data":[###ResponseData from Plugin###]} with AlpdeskCoreConstants::$STATUSCODE_OK
   * OR ErrorMessage with AlpdeskCoreConstants::$STATUSCODE_COMMONERROR
   * 
   */
  public function call(Request $request, UserInterface $user): JsonResponse {
    try {
      $plugindata = (array) json_decode($request->getContent(), true);
      $response = (new AlpdeskCorePlugin($this->rootDir))->call($user, $plugindata);
      $event = new AlpdeskCorePlugincallEvent($response);
      $this->eventService->getDispatcher()->dispatch($event, AlpdeskCorePlugincallEvent::NAME);
      $this->logger->info('username:' . $event->getResultData()->getUsername() . ' | Plugincall "' . $event->getResultData()->getPlugin() . '" successfully', __METHOD__);
      return $this->output($event->getResultData(), AlpdeskCoreConstants::$STATUSCODE_OK);
    } catch (\Exception | AlpdeskCorePluginException $exception) {
      $this->logger->error($exception->getMessage(), __METHOD__);
      return $this->outputError($exception->getMessage(), AlpdeskCoreConstants::$STATUSCODE_COMMONERROR);
    }
  }

}
