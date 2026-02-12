<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class AsAlpdeskCoreStorage
{
    public function __construct(
        public string $alias = ''
    )
    {
    }
}