<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\DependencyInjection\CompilerPass;

use Alpdesk\AlpdeskCore\Library\Storage\StorageAdapter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class StoragePass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @return void
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('alpdeskcore.storage_adapter')) {
            return;
        }

        $definition = $container->findDefinition('alpdeskcore.storage_adapter');

        $taggedServices = $container->findTaggedServiceIds('alpdeskcore.storage');

        foreach ($taggedServices as $id => $tags) {

            foreach ($tags as $attributes) {

                $definition->addMethodCall('addStorage', [
                    new Reference($id),
                    $attributes['alias']
                ]);

            }

        }

    }

}