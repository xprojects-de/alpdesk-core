<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage;

use Contao\File;
use Contao\Folder;

class StorageObject
{
    public ?string $path = null;
    public ?string $absolutePath = null;
    public ?string $name = null;
    public ?string $url = null;
    public ?string $uuid = null;
    public ?string $filename = null;
    public ?File $file = null;
    public ?Folder $folder = null;
}