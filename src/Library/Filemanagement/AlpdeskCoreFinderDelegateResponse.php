<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Filemanagement;

use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AlpdeskCoreFinderDelegateResponse
{
    private ?JsonResponse $response = null;

    private Request $request;
    private AlpdeskcoreUser $user;

    public function __construct(Request $request, AlpdeskcoreUser $user)
    {
        $this->request = $request;
        $this->user = $user;
    }

    public function getResponse(): ?JsonResponse
    {
        return $this->response;
    }

    public function setResponse(?JsonResponse $response): void
    {
        $this->response = $response;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getUser(): AlpdeskcoreUser
    {
        return $this->user;
    }

}
