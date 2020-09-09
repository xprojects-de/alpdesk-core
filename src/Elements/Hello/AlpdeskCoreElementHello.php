<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Elements\Hello;

use Alpdesk\AlpdeskCore\Elements\AlpdeskCoreElement;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;

class AlpdeskCoreElementHello extends AlpdeskCoreElement {

  public function execute(AlpdescCoreBaseMandantInfo $mandantInfo, array $data): array {
    return array('Mandant' => $mandantInfo->getMandant(), 'Value' => 'Hello AlpdeskPlugin');
  }

}
