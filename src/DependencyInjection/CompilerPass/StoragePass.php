<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\DependencyInjection\CompilerPass;

use Alpdesk\AlpdeskCore\DependencyInjection\AlpdeskCoreExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

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

        $taggedServices = $container->findTaggedServiceIds(AlpdeskCoreExtension::STORAGE_TAG);

        foreach ($taggedServices as $id => $tags) {

            foreach ($tags as $attributes) {

                $alias = $attributes['alias'] ?? null;
                if (!\is_string($alias) || $alias === '') {
                    throw new InvalidArgumentException(sprintf('The service "%s" tagged as "%s" must have a non-empty "alias" attribute.', $id, AlpdeskCoreExtension::STORAGE_TAG));
                }

                $definition->addMethodCall('addStorage', [new Reference($id), $alias]);

            }

        }

    }

}