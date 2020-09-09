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

class Plugin implements BundlePluginInterface, RoutingPluginInterface, ExtensionPluginInterface {

  public function getBundles(ParserInterface $parser) {
    return [BundleConfig::create(AlpdeskCoreBundle::class)->setLoadAfter([ContaoCoreBundle::class])];
  }

  public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel) {
    $file = __DIR__ . '/../Resources/config/routes.yml';
    return $resolver->resolve($file)->load($file);
  }

  public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container) {
    if ('security' !== $extensionName) {
      return $extensionConfigs;
    }
    foreach ($extensionConfigs as &$extensionConfig) {
      if (isset($extensionConfig['firewalls'])) {
        $extensionConfig['providers']['alpdeskcore.security.user_provider'] = [
            'id' => 'alpdeskcore.security.user_provider'
        ];
        $guard = [
            'authenticators' => [
                'alpdeskcore.security.token_authenticator'
            ],
        ];
        $offset = (int) array_search('frontend', array_keys($extensionConfig['firewalls']));
        $extensionConfig['firewalls'] = array_merge(
                array_slice($extensionConfig['firewalls'], 0, $offset, true),
                [
                    'alpdeskcore_auth_verify' => [
                        'pattern' => '/auth/verify',
                        'anonymous' => true,
                        'stateless' => true,
                        'guard' => $guard,
                        'provider' => 'alpdeskcore.security.user_provider',
                    ],
                    'alpdeskcore_auth_logout' => [
                        'pattern' => '/auth/logout',
                        'anonymous' => true,
                        'stateless' => true,
                        'guard' => $guard,
                        'provider' => 'alpdeskcore.security.user_provider',
                    ],
                    'alpdeskcore_plugin' => [
                        'pattern' => '/plugin',
                        'anonymous' => true,
                        'stateless' => true,
                        'guard' => $guard,
                        'provider' => 'alpdeskcore.security.user_provider',
                    ],
                    'alpdeskcore_mandant_list' => [
                        'pattern' => '/mandant',
                        'anonymous' => true,
                        'stateless' => true,
                        'guard' => $guard,
                        'provider' => 'alpdeskcore.security.user_provider',
                    ],
                    'alpdeskcore_mandant_edit' => [
                        'pattern' => '/mandant/edit',
                        'anonymous' => true,
                        'stateless' => true,
                        'guard' => $guard,
                        'provider' => 'alpdeskcore.security.user_provider',
                    ],
                    'alpdeskcore_filedownload' => [
                        'pattern' => '/download',
                        'anonymous' => true,
                        'stateless' => true,
                        'guard' => $guard,
                        'provider' => 'alpdeskcore.security.user_provider',
                    ],
                    'alpdeskcore_fileupload' => [
                        'pattern' => '/upload',
                        'anonymous' => true,
                        'stateless' => true,
                        'guard' => $guard,
                        'provider' => 'alpdeskcore.security.user_provider',
                    ],
                ],
                array_slice($extensionConfig['firewalls'], $offset, null, true)
        );
        break;
      }
    }
    return $extensionConfigs;
  }

}
