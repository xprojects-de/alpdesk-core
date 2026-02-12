<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Logs;

use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Utils\Utils;
use Contao\BackendUser;
use Contao\Controller;
use Contao\File;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
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

                $lines = $logFile->getContentAsArray();

                if ($filterValue !== null && $filterValue !== '' && \count($lines) > 0) {

                    $filteredLines = [];

                    foreach ($lines as $line) {

                        if (\str_contains((string)$line, $filterValue)) {
                            $filteredLines[] = \str_replace($filterValue, '<strong class="filterMarked">' . $filterValue . '</strong>', $line);
                        }

                    }

                    $lines = $filteredLines;

                }

                if (\count($lines) > 0) {

                    $arrReturn[] = [
                        'logfile' => $logFile->name
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
        $GLOBALS['TL_CSS'][] = 'bundles/alpdeskcore/logs/alpdeskcore_logs.css';

        $backendUser = $this->security->getUser();

        if (!$backendUser instanceof BackendUser) {

            return $this->render('@AlpdeskCore/alpdeskcorelogs_error.html.twig', [
                'headline' => 'Error',
                'error' => 'invalid access'
            ]);

        }

        Utils::mergeUserGroupPermissions($backendUser);

        if (!$backendUser->isAdmin && (int)$backendUser->alpdeskcorelogs_enabled !== 1) {

            return $this->render('@AlpdeskCore/alpdeskcorelogs_error.html.twig', [
                'headline' => 'Error',
                'error' => 'invalid access'
            ]);

        }

        $this->deleteLog();
        $this->checkFilter();

        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/alpdeskcore/logs/alpdeskcore_logs.js';

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
            'logs' => $logFiles,
            'headline' => 'Logs'
        ]);

    }

    public function lazyLogs(Request $request): JsonResponse
    {
        try {

            $csrfToken = $request->headers->get('contaoCsrfToken');
            $csrfTokenObject = new CsrfToken($this->csrfTokenName, $csrfToken);

            if (!$this->csrfTokenManager->isTokenValid($csrfTokenObject)) {
                throw new \Exception('invalid csrfToken');
            }

            $backendUser = $this->security->getUser();

            if (!$backendUser instanceof BackendUser) {
                throw new \Exception('invalid access');
            }

            Utils::mergeUserGroupPermissions($backendUser);

            if (!$backendUser->isAdmin && (int)$backendUser->alpdeskcorelogs_enabled !== 1) {
                throw new \Exception('invalid access');
            }

            $requestBody = $request->getContent();
            if (!\is_string($requestBody) || $requestBody === '') {
                throw new \Exception('invalid payload');
            }

            $requestBodyObject = \json_decode($requestBody, true, 512, JSON_THROW_ON_ERROR);
            if (
                !\is_array($requestBodyObject) ||
                !\array_key_exists('logFileName', $requestBodyObject) ||
                $requestBodyObject['logFileName'] === null || $requestBodyObject['logFileName'] === ''
            ) {
                throw new \Exception('invalid payload');
            }

            $logFile = new File('var/logs/' . $requestBodyObject['logFileName']);
            if ($logFile->extension !== 'log' || !$logFile->exists()) {
                throw new \Exception('invalid extension or file does not exists');
            }

            $filterValue = ($requestBodyObject['filterValue'] ?? null);
            if (\is_string($filterValue) && $filterValue !== '') {
                $filterValue = StringUtil::specialchars($filterValue);
            }

            $filteredLines = [];

            $lines = $logFile->getContentAsArray();
            if (\is_array($lines) && \count($lines) > 0) {

                foreach ($lines as $line) {

                    if (\trim($line) === '') {
                        continue;
                    }

                    $filteredLines[] = StringUtil::specialchars($line);

                }

            }

            if (
                $filterValue !== null && $filterValue !== '' &&
                \count($filteredLines) > 0
            ) {

                $newFilteredLines = [];

                foreach ($filteredLines as $filteredLine) {

                    if (\str_contains((string)$filteredLine, $filterValue)) {
                        $newFilteredLines[] = \str_replace($filterValue, '<strong class="filterMarked">' . $filterValue . '</strong>', $filteredLine);
                    }

                }

                $filteredLines = $newFilteredLines;

            }

            return (new JsonResponse([
                'error' => false,
                'fileName' => 'var/logs/' . $requestBodyObject['logFileName'],
                'message' => '',
                'content' => $filteredLines
            ], AlpdeskCoreConstants::$STATUSCODE_OK));

        } catch (\Throwable $tr) {

            return (new JsonResponse([
                'error' => true,
                'message' => $tr->getMessage()
            ], AlpdeskCoreConstants::$STATUSCODE_COMMONERROR));

        }

    }

}
