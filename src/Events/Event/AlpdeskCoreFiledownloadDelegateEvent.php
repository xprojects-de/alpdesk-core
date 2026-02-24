<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Event;

use Alpdesk\AlpdeskCore\Library\Filemanagement\AlpdeskCoreFiledownloadDelegateResponse;
use Symfony\Contracts\EventDispatcher\Event;

class AlpdeskCoreFiledownloadDelegateEvent extends Event
{
    public const string NAME = 'alpdesk.filedownloaddelegate';

    private AlpdeskCoreFiledownloadDelegateResponse $delegateResponse;

    public function __construct(AlpdeskCoreFiledownloadDelegateResponse $delegateResponse)
    {
        $this->delegateResponse = $delegateResponse;
    }

    public function getDelegateResponse(): AlpdeskCoreFiledownloadDelegateResponse
    {
        return $this->delegateResponse;
    }
}
