<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Alpdesk\AlpdeskCore\Library\Filemanagement\AlpdeskCoreFileuploadResponse;

class AlpdeskCoreFileuploadEvent extends Event {

  public const NAME = 'alpdesk.fileupload';

  private AlpdeskCoreFileuploadResponse $resultData;

  public function __construct(AlpdeskCoreFileuploadResponse $resultData) {
    $this->resultData = $resultData;
  }

  public function getResultData(): AlpdeskCoreFileuploadResponse {
    return $this->resultData;
  }

  public function setResultData(AlpdeskCoreFileuploadResponse $resultData) {
    $this->resultData = $resultData;
  }

}
