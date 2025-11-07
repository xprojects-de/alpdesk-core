<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Listener;

use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreRegisterPlugin;

class AlpdeskCoreRegisterPluginListener
{
    public function __invoke(AlpdeskCoreRegisterPlugin $event): void
    {
        $data = $event->getPluginData();
        $info = $event->getPluginInfo();

        $data['contaoCrud'] = $GLOBALS['TL_LANG']['ADME']['contaoCrudPlugin'];
        $info['contaoCrud'] = ['customTemplate' => false];

        $event->setPluginData($data);
        $event->setPluginInfo($info);
    }
}
