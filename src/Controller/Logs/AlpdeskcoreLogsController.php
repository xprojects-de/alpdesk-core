<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Logs;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment as TwigEnvironment;
use Contao\Environment;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Alpdesk\AlpdeskFrontendediting\Utils\Utils;

class AlpdeskcoreLogsController extends AbstractController
{

    private $twig;
    private $csrfTokenManager = null;
    private $csrfTokenName;
    protected $router;
    private $security;

    public function __construct(TwigEnvironment $twig, CsrfTokenManagerInterface $csrfTokenManager, string $csrfTokenName, RouterInterface $router, Security $security)
    {
        $this->twig = $twig;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->router = $router;
        $this->security = $security;
    }

    public function endpoint(): Response
    {
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/alpdeskcore/js/alpdeskcore_logs.js';
        $GLOBALS['TL_CSS'][] = 'bundles/alpdeskcore/css/alpdeskcore_logs.css';

        $outputTwig = $this->twig->render('@AlpdeskCore/alpdeskcorelogs_be.html.twig', [
            'token' => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
            'base' => Environment::get('base'),
            'msg' => 'Hallo Welt'
        ]);

        return new Response($outputTwig);
    }

}
