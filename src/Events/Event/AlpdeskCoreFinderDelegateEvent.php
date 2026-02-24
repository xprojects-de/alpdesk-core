<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Event;

use Alpdesk\AlpdeskCore\Library\Filemanagement\AlpdeskCoreFinderDelegateResponse;
use Symfony\Contracts\EventDispatcher\Event;

class AlpdeskCoreFinderDelegateEvent extends Event
{
    public const string NAME = 'alpdesk.finderdelegate';

    private AlpdeskCoreFinderDelegateResponse $delegateResponse;

    public function __construct(AlpdeskCoreFinderDelegateResponse $delegateResponse)
    {
        $this->delegateResponse = $delegateResponse;
    }

    public function getDelegateResponse(): AlpdeskCoreFinderDelegateResponse
    {
        return $this->delegateResponse;
    }
}
