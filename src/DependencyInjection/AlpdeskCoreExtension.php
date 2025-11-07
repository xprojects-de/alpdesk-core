<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\DependencyInjection;

use Alpdesk\AlpdeskCore\Library\Storage\AsAlpdeskCoreStorage;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AlpdeskCoreExtension extends Extension
{
    public const string STORAGE_TAG = 'alpdeskcore.storage';

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('listener.yml');
        $loader->load('services.yml');

        $container->registerAttributeForAutoconfiguration(
            AsAlpdeskCoreStorage::class,
            static function (ChildDefinition $definition, AsAlpdeskCoreStorage $attribute): void {
                $definition->addTag(self::STORAGE_TAG, ['alias' => $attribute->alias]);
            }
        );

    }

}
