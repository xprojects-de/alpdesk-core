<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Client;

use Contao\CoreBundle\Framework\ContaoFramework;
use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Contao\Environment;
use Symfony\Component\Filesystem\Filesystem;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;

class AlpdeskCoreClientController extends Controller {

  protected ContaoFramework $framework;
  protected AlpdeskCoreEventService $eventService;
  protected AlpdeskcoreLogger $logger;
  protected string $rootDir;

  public function __construct(ContaoFramework $framework, AlpdeskCoreEventService $eventService, AlpdeskcoreLogger $logger, string $rootDir) {
    $this->framework = $framework;
    $this->framework->initialize();
    $this->eventService = $eventService;
    $this->logger = $logger;
    $this->rootDir = $rootDir;
  }

  public function client(Request $request): RedirectResponse {
    $filesystem = new Filesystem();
    try {
      $configFile = $this->rootDir . '/web/alpdeskclient/assets/config/config.prod.json';
      $clientFile = $this->rootDir . '/web/alpdeskclient/client.html';
      if ($filesystem->exists($clientFile) && $filesystem->exists($configFile)) {
        $settings = json_decode(\file_get_contents($configFile), true);
        if ($settings !== null && \is_array($settings) && isset($settings['apiServer']['rest'])) {
          $stillModified = false;
          if (isset($settings['modified'])) {
            $stillModified = $settings['modified'];
          }
          if ($stillModified == false) {
            $settings['apiServer']['rest'] = substr(Environment::get('base'), 0, (strlen(Environment::get('base')) - 1));
            $settings['modified'] = true;
            $newSettings = json_encode($settings);
            $newConfigFile = \fopen($configFile, "wb");
            \fwrite($newConfigFile, $newSettings);
            \fclose($newConfigFile);
            $this->logger->info('Client-Config modified successfully', __METHOD__);
          }
          return (new RedirectResponse(Environment::get('base') . 'alpdeskclient/client.html'));
        }
        $this->logger->error('Error modify Client-Config', __METHOD__);
      }
    } catch (\Exception $ex) {
      $this->logger->error($ex->getMessage(), __METHOD__);
    }
    return (new RedirectResponse(Environment::get('base')));
  }

}
