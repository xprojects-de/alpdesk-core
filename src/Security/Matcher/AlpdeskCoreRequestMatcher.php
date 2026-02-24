<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security\Matcher;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class AlpdeskCoreRequestMatcher implements RequestMatcherInterface
{
    /**
     * @param Request $request
     * @return bool
     */
    public function matches(Request $request): bool
    {
        return $request->attributes->get('_scope') === 'alpdeskapi';
    }

}