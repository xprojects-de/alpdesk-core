<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Listener;

use Alpdesk\AlpdeskCore\Utils\Utils;
use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Contao\BackendUser;

class AlpdeskCoreBackendMenuListener
{
    protected RouterInterface $router;
    protected RequestStack $requestStack;
    private Security $security;

    public function __construct(Security $security, RouterInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    public function __invoke(MenuEvent $event): void
    {
        $backendUser = $this->security->getUser();

        if (!$backendUser instanceof BackendUser) {
            return;
        }

        Utils::mergeUserGroupPermissions($backendUser);

        if (!$backendUser->isAdmin && (int)$backendUser->alpdeskcorelogs_enabled !== 1) {
            return;
        }

        $factory = $event->getFactory();
        $tree = $event->getTree();

        if ('mainMenu' === $tree->getName() && $this->requestStack->getCurrentRequest() !== null) {

            $contentNode = $tree->getChild('alpdeskcore');
            if ($contentNode === null) {
                $contentNode = $tree->getChild('system');
            }

            $node = $factory
                ->createItem('alpdesk_logs_backend')
                ->setUri($this->router->generate('alpdesk_logs_backend'))
                ->setLabel('Logs')
                ->setLinkAttribute('title', 'Logs')
                ->setLinkAttribute('class', 'alpdesk_logs_backend')
                ->setLinkAttribute('data-turbo-prefetch', 'false')
                ->setCurrent($this->requestStack->getCurrentRequest()->get('_route') === 'alpdesk_logs_backend');

            $contentNode?->addChild($node);

        }
    }

}
