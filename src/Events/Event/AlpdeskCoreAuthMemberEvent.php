<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Alpdesk\AlpdeskCore\Library\Auth\AlpdeskCoreMemberResponse;

class AlpdeskCoreAuthMemberEvent extends Event {

  public const NAME = 'alpdesk.auth_member';

  private AlpdeskCoreMemberResponse $resultData;

  public function __construct(AlpdeskCoreMemberResponse $resultData) {
    $this->resultData = $resultData;
  }

  public function getResultData(): AlpdeskCoreMemberResponse {
    return $this->resultData;
  }

  public function setResultData(AlpdeskCoreMemberResponse $resultData) {
    $this->resultData = $resultData;
  }

}
