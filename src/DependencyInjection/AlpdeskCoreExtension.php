<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AlpdeskCoreExtension extends Extension
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('listener.yml');
        $loader->load('services.yml');

        /**
         * e.g. in config.xml
         * alpdesk_core:
         *   storage:
         *     awss3:
         *       key: "myKey"
         *       secret: "mySecret"
         *       region: "eu-central-1"
         *       bucket: "bucketName"
         */

        if (!isset($config['storage']) || !\is_array($config['storage'])) {
            $config['storage'] = [];
        }

        $container->setParameter('alpdesk_core.storage', $config['storage']);

    }

}
