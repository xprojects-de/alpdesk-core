<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Listener;

use Alpdesk\AlpdeskCore\Utils\Utils;
use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Contao\BackendUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class AlpdeskCoreBackendMenuListener
{
    protected RouterInterface $router;
    protected RequestStack $requestStack;
    private Security $security;
    private TranslatorInterface $translator;

    public function __construct(Security $security, RouterInterface $router, RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->security = $security;
        $this->translator = $translator;
    }

    public function __invoke(MenuEvent $event): void
    {
        $backendUser = $this->security->getUser();

        if (!$backendUser instanceof BackendUser) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return;
        }

        $factory = $event->getFactory();
        $tree = $event->getTree();

        if ('mainMenu' === $tree->getName()) {

            $contentNode = $tree->getChild('alpdeskcore');
            if ($contentNode === null) {
                $contentNode = $tree->getChild('system');
            }

            $databaseNodeLabel = $this->translator->trans('MOD.alpdeskcore_databasemanager.0', [], 'contao_modules');

            $databaseNodeData = [
                'do' => 'alpdeskcore_databasemanager',
                'ref' => $request->attributes->get('_contao_referer_id'),
            ];

            $databaseNode = $factory
                ->createItem('alpdeskcore_databasemanager')
                ->setLabel($databaseNodeLabel)
                ->setLinkAttribute('title', $databaseNodeLabel)
                ->setUri($this->router->generate('contao_backend', $databaseNodeData))
                ->setLinkAttribute('class', 'alpdeskcore_databasemanager')
                ->setLinkAttribute('data-turbo-prefetch', 'false')
                ->setExtra('safe_label', true)
                ->setExtra('translation_domain', false);

            $contentNode?->addChild($databaseNode);

            Utils::mergeUserGroupPermissions($backendUser);

            if (!$backendUser->isAdmin && (int)$backendUser->alpdeskcorelogs_enabled !== 1) {
                return;
            }

            $logNode = $factory
                ->createItem('alpdesk_logs_backend')
                ->setUri($this->router->generate('alpdesk_logs_backend'))
                ->setLabel('Logs')
                ->setLinkAttribute('title', 'Logs')
                ->setLinkAttribute('class', 'alpdesk_logs_backend')
                ->setLinkAttribute('data-turbo-prefetch', 'false')
                ->setCurrent($request->get('_route') === 'alpdesk_logs_backend');

            $contentNode?->addChild($logNode);

        }
    }

}
