<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('alpdesk_core');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->arrayNode('storage')
                    ->arrayPrototype()
                        ->prototype('scalar')->end()
                    ->end()
                ->defaultValue([])
                ->end()
            ->end();

        return $treeBuilder;

    }

}