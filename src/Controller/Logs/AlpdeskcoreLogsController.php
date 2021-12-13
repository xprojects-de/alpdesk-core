<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Logs;

use Contao\Controller;
use Contao\File;
use Contao\Input;
use Contao\System;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment as TwigEnvironment;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class AlpdeskcoreLogsController extends AbstractController
{
    private TwigEnvironment $twig;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private string $csrfTokenName;
    protected RouterInterface $router;
    private Security $security;
    private string $projectDir;

    public function __construct(TwigEnvironment $twig, CsrfTokenManagerInterface $csrfTokenManager, string $csrfTokenName, RouterInterface $router, Security $security, string $projectDir)
    {
        $this->twig = $twig;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->router = $router;
        $this->security = $security;
        $this->projectDir = $projectDir;
    }

    /**
     * @param string $startString
     * @param string $string
     * @return bool
     */
    private static function startsWith(string $startString, string $string): bool
    {
        $len = \strlen($startString);
        $sub = \substr($string, 0, $len);

        return ($sub === $startString);
    }

    /**
     * @param string $parseFolder
     * @return array
     * @throws \Exception
     */
    private function scanDir(string $parseFolder): array
    {
        $strFolder = $this->projectDir . '/' . $parseFolder;

        $arrReturn = [];

        foreach (\scandir($strFolder, SCANDIR_SORT_ASCENDING) as $strFile) {

            if ($strFile === '.' || $strFile === '..' || self::startsWith('.', $strFile)) {
                continue;
            }

            $logFile = new File($parseFolder . '/' . $strFile);
            if ($logFile->extension === 'log' && $logFile->exists()) {

                $content = $logFile->getContentAsArray();

                $arrReturn[] = [
                    'logfile' => $logFile->name,
                    'content' => $content
                ];

            }
        }

        \usort($arrReturn, static function ($a, $b) {
            return $b['logfile'] <=> $a['logfile'];
        });

        return $arrReturn;
    }

    /**
     * @param string $parseFolder
     * @return void
     * @throws \Exception
     */
    private function deleteLog(string $parseFolder): void
    {
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
     * @return Response
     * @throws \Exception
     */
    public function endpoint(): Response
    {
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/alpdeskcore/js/alpdeskcore_logs.js';
        $GLOBALS['TL_CSS'][] = 'bundles/alpdeskcore/css/alpdeskcore_logs.css';

        System::loadLanguageFile('default');

        $this->deleteLog('var/logs');
        $logFiles = $this->scanDir('var/logs');

        $numberOfLogs = \count($logFiles);

        $outputTwig = $this->twig->render('@AlpdeskCore/alpdeskcorelogs_be.html.twig', [
            'token' => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
            'route' => $this->router->generate('alpdesk_logs_backend'),
            'confirm' => $GLOBALS['TL_LANG']['MOD']['alpdeskcorelogs_confirm'],
            'numberOfLogs' => $GLOBALS['TL_LANG']['MOD']['alpdeskcorelogs_logcount'] . ': ' . $numberOfLogs,
            'logs' => $logFiles
        ]);

        return new Response($outputTwig);
    }

}
