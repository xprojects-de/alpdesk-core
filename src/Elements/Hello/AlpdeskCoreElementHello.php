<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Elements\Hello;

use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCorePlugincallEvent;

class AlpdeskCoreElementHello {

  public function __invoke(AlpdeskCorePlugincallEvent $event): void {

    if ('hello' !== $event->getResultData()->getPlugin()) {
      return;
    }

    $requestdata = $event->getResultData()->getRequestData();

    $data = [
        'Mandant' => $event->getResultData()->getMandantInfo()->getMandant(),
        'Value' => 'Hello AlpdeskPlugin'
    ];

    $event->getResultData()->setData($data);
  }

}
