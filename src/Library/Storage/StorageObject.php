<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage;

use Contao\File;
use Contao\Folder;

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
    public ?File $file = null;
    public ?Folder $folder = null;
    public bool $isPublic = false;
    public bool $isImage = false;
    public int $size = 0;
}