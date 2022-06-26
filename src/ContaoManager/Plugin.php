<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Alpdesk\AlpdeskCore\AlpdeskCoreBundle;
use Symfony\Component\Routing\RouteCollection;

class Plugin implements BundlePluginInterface, RoutingPluginInterface, ExtensionPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [BundleConfig::create(AlpdeskCoreBundle::class)->setLoadAfter([ContaoCoreBundle::class])];
    }

    /**
     * @param LoaderResolverInterface $resolver
     * @param KernelInterface $kernel
     * @return RouteCollection|null
     * @throws \Exception
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        $file = __DIR__ . '/../Resources/config/routes.yml';
        return $resolver->resolve($file)->load($file);
    }

    /**
     * @param string $extensionName
     * @param array $extensionConfigs
     * @param ContainerBuilder $container
     * @return array
     */
    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container): array
    {
        if ('security' !== $extensionName) {
            return $extensionConfigs;
        }

        foreach ($extensionConfigs as &$extensionConfig) {

            if (isset($extensionConfig['firewalls'])) {

                $extensionConfig['providers']['alpdeskcore.security.user_provider'] = [
                    'id' => 'alpdeskcore.security.user_provider'
                ];

                $offset = (int)array_search('frontend', array_keys($extensionConfig['firewalls']), false);

                $extensionConfig['firewalls'] = array_merge(
                    array_slice($extensionConfig['firewalls'], 0, $offset, true),
                    [
                        'alpdeskcore_api' => [
                            'request_matcher' => 'alpdeskcore.routing.scope_matcher',
                            'anonymous' => true,
                            'lazy' => true,
                            'stateless' => true,
                            'custom_authenticators' => 'alpdeskcore.security.token_authenticator'
                        ]
                    ],
                    array_slice($extensionConfig['firewalls'], $offset, null, true)
                );

                break;

            }

        }

        return $extensionConfigs;

    }
}
