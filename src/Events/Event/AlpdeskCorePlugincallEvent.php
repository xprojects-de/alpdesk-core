<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Alpdesk\AlpdeskCore\Library\Plugin\AlpdeskCorePlugincallResponse;

class AlpdeskCorePlugincallEvent extends Event {

  public const NAME = 'alpdesk.plugincall';

  private AlpdeskCorePlugincallResponse $resultData;

  public function __construct(AlpdeskCorePlugincallResponse $resultData) {
    $this->resultData = $resultData;
  }

  public function getResultData(): AlpdeskCorePlugincallResponse {
    return $this->resultData;
  }

  public function setResultData(AlpdeskCorePlugincallResponse $resultData) {
    $this->resultData = $resultData;
  }

}
