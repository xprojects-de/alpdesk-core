<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Logs;

use Alpdesk\AlpdeskCore\Utils\Utils;
use Contao\BackendUser;
use Contao\Controller;
use Contao\File;
use Contao\Input;
use Contao\System;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Contao\CoreBundle\Controller\AbstractBackendController;

class AlpdeskcoreLogsController extends AbstractBackendController
{
    private CsrfTokenManagerInterface $csrfTokenManager;
    private string $csrfTokenName;
    protected RouterInterface $router;
    private string $projectDir;
    private RequestStack $requestStack;
    private Security $security;

    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        string                    $csrfTokenName,
        RouterInterface           $router,
        string                    $projectDir,
        RequestStack              $requestStack,
        Security                  $security
    )
    {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->router = $router;
        $this->projectDir = $projectDir;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    /**
     * @return SessionInterface|null
     */
    private function getCurrentSession(): ?SessionInterface
    {
        return $this->requestStack->getCurrentRequest()?->getSession();
    }

    /**
     * @param string|null $filterValue
     * @return array
     * @throws \Exception
     */
    private function scanDir(?string $filterValue = null): array
    {
        $parseFolder = 'var/logs';
        $strFolder = $this->projectDir . '/' . $parseFolder;

        $arrReturn = [];

        foreach (\scandir($strFolder, SCANDIR_SORT_ASCENDING) as $strFile) {

            if ($strFile === '.' || $strFile === '..' || \str_starts_with($strFile, '.')) {
                continue;
            }

            $logFile = new File($parseFolder . '/' . $strFile);
            if ($logFile->extension === 'log' && $logFile->exists()) {

                $content = $logFile->getContentAsArray();

                if ($filterValue !== null && $filterValue !== '' && \count($content) > 0) {

                    $newContent = [];

                    foreach ($content as $contentItem) {

                        if (\str_contains((string)$contentItem, $filterValue)) {
                            $newContent[] = \str_replace($filterValue, '<strong class="filterMarked">' . $filterValue . '</strong>', $contentItem);
                        }

                    }

                    $content = $newContent;

                }

                if (\count($content) > 0) {

                    $arrReturn[] = [
                        'logfile' => $logFile->name,
                        'content' => $content
                    ];

                }

            }
        }

        \usort($arrReturn, static function ($a, $b) {
            return $b['logfile'] <=> $a['logfile'];
        });

        return $arrReturn;
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function deleteLog(): void
    {
        $parseFolder = 'var/logs';
        $deleteLog = Input::get('deleteLog');
        if ($deleteLog !== null && $deleteLog !== '') {

            $logFile = new File($parseFolder . '/' . $deleteLog);
            if ($logFile->exists()) {
                $logFile->delete();
            }

            Controller::redirect($this->router->generate('alpdesk_logs_backend'));

        }

    }

    /**
     * @return void
     */
    private function checkFilter(): void
    {
        if (Input::post('setFilter')) {

            $filterValue = Input::postRaw('filterValue');

            if ($filterValue !== null) {
                $this->getCurrentSession()?->set('alpdeskcore_logsfilter', $filterValue);
            } else {
                $this->getCurrentSession()?->set('alpdeskcore_logsfilter', null);
            }

            Controller::redirect($this->router->generate('alpdesk_logs_backend'));

        }

    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function endpoint(): Response
    {
        $GLOBALS['TL_CSS'][] = 'bundles/alpdeskcore/css/alpdeskcore_logs.css';

        $backendUser = $this->security->getUser();

        if (!$backendUser instanceof BackendUser) {

            return $this->render('@AlpdeskCore/alpdeskcorelogs_error.html.twig', [
                'errorMessage' => 'invalid access'
            ]);

        }

        Utils::mergeUserGroupPermissions($backendUser);

        if (!$backendUser->isAdmin && (int)$backendUser->alpdeskcorelogs_enabled !== 1) {

            return $this->render('@AlpdeskCore/alpdeskcorelogs_error.html.twig', [
                'errorMessage' => 'invalid access'
            ]);

        }

        $this->deleteLog();
        $this->checkFilter();

        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/alpdeskcore/js/alpdeskcore_logs.js';

        System::loadLanguageFile('default');

        $filterValue = $this->getCurrentSession()?->get('alpdeskcore_logsfilter');
        if ($filterValue === null) {
            $filterValue = '';
        }

        $logFiles = $this->scanDir($filterValue);

        $numberOfLogs = \count($logFiles);

        return $this->render('@AlpdeskCore/alpdeskcorelogs_be.html.twig', [
            'token' => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
            'route' => $this->router->generate('alpdesk_logs_backend'),
            'confirm' => $GLOBALS['TL_LANG']['MOD']['alpdeskcorelogs_confirm'],
            'numberOfLogs' => $GLOBALS['TL_LANG']['MOD']['alpdeskcorelogs_logcount'] . ': ' . $numberOfLogs,
            'filterValue' => $filterValue,
            'logs' => $logFiles
        ]);

    }

}
