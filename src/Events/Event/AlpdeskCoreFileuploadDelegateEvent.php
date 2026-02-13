<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Event;

use Alpdesk\AlpdeskCore\Library\Filemanagement\AlpdeskCoreFileuploadDelegateResponse;
use Symfony\Contracts\EventDispatcher\Event;

class AlpdeskCoreFileuploadDelegateEvent extends Event
{
    public const string NAME = 'alpdesk.fileuploaddelegate';

    private AlpdeskCoreFileuploadDelegateResponse $delegateResponse;

    public function __construct(AlpdeskCoreFileuploadDelegateResponse $delegateResponse)
    {
        $this->delegateResponse = $delegateResponse;
    }

    public function getDelegateResponse(): AlpdeskCoreFileuploadDelegateResponse
    {
        return $this->delegateResponse;
    }
}
