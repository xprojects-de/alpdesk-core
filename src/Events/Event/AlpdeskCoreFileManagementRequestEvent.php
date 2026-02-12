<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Event;

use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Symfony\Contracts\EventDispatcher\Event;

class AlpdeskCoreFileManagementRequestEvent extends Event
{
    public const string NAME = 'alpdesk.filemanagement.request.event';

    private string $action = '';
    private string $storageAdapter = 'local';
    private mixed $requestData = null;
    private ?AlpdeskcoreUser $user = null;

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getStorageAdapter(): string
    {
        return $this->storageAdapter;
    }

    public function setStorageAdapter(string $storageAdapter): void
    {
        $this->storageAdapter = $storageAdapter;
    }

    public function getRequestData(): mixed
    {
        return $this->requestData;
    }

    public function setRequestData(mixed $requestData): void
    {
        $this->requestData = $requestData;
    }

    public function getUser(): ?AlpdeskcoreUser
    {
        return $this->user;
    }

    public function setUser(?AlpdeskcoreUser $user): void
    {
        $this->user = $user;
    }

}
