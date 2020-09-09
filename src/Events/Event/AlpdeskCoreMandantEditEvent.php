<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdeskCoreMandantResponse;

class AlpdeskCoreMandantEditEvent extends Event {

  public const NAME = 'alpdesk.mandantedit';

  private AlpdeskCoreMandantResponse $resultData;

  public function __construct(AlpdeskCoreMandantResponse $resultData) {
    $this->resultData = $resultData;
  }

  public function getResultData(): AlpdeskCoreMandantResponse {
    return $this->resultData;
  }

  public function setResultData(AlpdeskCoreMandantResponse $resultData) {
    $this->resultData = $resultData;
  }

}
