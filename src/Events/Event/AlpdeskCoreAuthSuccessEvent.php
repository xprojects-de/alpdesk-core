<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Alpdesk\AlpdeskCore\Library\Auth\AlpdeskCoreAuthResponse;

class AlpdeskCoreAuthSuccessEvent extends Event {

  public const NAME = 'alpdesk.auth_success';

  private AlpdeskCoreAuthResponse $resultData;

  public function __construct(AlpdeskCoreAuthResponse $resultData) {
    $this->resultData = $resultData;
  }

  public function getResultData(): AlpdeskCoreAuthResponse {
    return $this->resultData;
  }

  public function setResultData(AlpdeskCoreAuthResponse $resultData) {
    $this->resultData = $resultData;
  }

}
