<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage;

class StorageObject
{
    public ?string $path = null;
    public ?string $basename = null;
    public ?string $extension = null;
    public ?string $absolutePath = null;
    public ?string $name = null;
    public ?string $url = null;
    public ?string $uuid = null;
    public ?string $filename = null;
    public ?string $type = null;
    public array|string|null $meta = null;
    public bool $isPublic = false;
    public bool $isImage = false;
    public int $size = 0;
}