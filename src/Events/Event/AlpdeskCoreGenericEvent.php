<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Event;

use Symfony\Contracts\EventDispatcher\Event;

class AlpdeskCoreGenericEvent extends Event
{
    private array $data;

    /**
     * AlpdeskCoreGenericEvent constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

}