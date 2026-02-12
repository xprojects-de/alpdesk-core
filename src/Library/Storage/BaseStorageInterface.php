<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage;

use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;

interface BaseStorageInterface
{
    public function getRootDir(): string;

    public function getPublicDir(): string;

    public function get(mixed $strUuid): ?StorageObject;

    public function delete(mixed $strUuid): void;

    public function exists(mixed $strUuid): bool;

    public function deploy(?string $localPath, ?string $remotePath, bool $override): ?StorageObject;

    public function synchronize(mixed $strUuid): void;

    public function createFile(string $filePath, mixed $content): ?StorageObject;

    public function createDirectory(string $filePath): ?StorageObject;

    public function setMeta(StorageObject $object): void;

    public function rename(string $srcPath, string $destFileName): ?StorageObject;

    public function move(string $srcPath, string $destPath): ?StorageObject;

    public function copy(string $srcPath, string $destPath): ?StorageObject;

    public function generateLocalUrl(?string $path): ?string;

    public function listDir(string $path): array;

    public function addMandant(?AlpdescCoreBaseMandantInfo $mandant): void;

    public function write(mixed $contents, string $path): void;

}