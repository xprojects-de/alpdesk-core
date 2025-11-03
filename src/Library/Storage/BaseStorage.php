<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage;

abstract class BaseStorage
{
    protected string $rootDir;

    /**
     * @param string $rootDir
     */
    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    abstract public function initialize(?array $config): void;

    abstract public function getRootDir(): string;

    abstract public function getPublicDir(): string;

    abstract public function get(mixed $strUuid): ?StorageObject;

    abstract public function delete(mixed $strUuid): void;

    abstract public function exists(mixed $strUuid): bool;

    abstract public function deploy(?string $localPath, ?string $remotePath, bool $override): ?StorageObject;

    abstract public function synchronize(mixed $strUuid): void;

    abstract public function createFile(string $filePath, mixed $content): ?StorageObject;

    abstract public function createDirectory(string $filePath): ?StorageObject;

    abstract public function setMeta(StorageObject $object): void;

    abstract public function rename(string $srcPath, string $destFileName): ?StorageObject;

    abstract public function move(string $srcPath, string $destPath): ?StorageObject;

    abstract public function copy(string $srcPath, string $destPath): ?StorageObject;

    abstract public function generateLocalUrl(?string $path): ?string;

    abstract public function listDir(string $path): array;

}