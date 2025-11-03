<?php

namespace Alpdesk\AlpdeskCore;

use Alpdesk\AlpdeskCore\DependencyInjection\CompilerPass\StoragePass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AlpdeskCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new StoragePass());
    }
}
