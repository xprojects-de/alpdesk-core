<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage;

use Alpdesk\AlpdeskCore\Library\Storage\Local\LocalStorage;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\StringUtil;

class StorageAdapter
{
    private ?array $storageConfig;

    private array $storageMap;

    /**
     * @param VirtualFilesystemInterface $filesStorage
     * @param string $rootDir
     * @param array|null $storageConfig
     */
    public function __construct(
        VirtualFilesystemInterface $filesStorage,
        string                     $rootDir,
        ?array                     $storageConfig
    )
    {
        $this->storageConfig = $storageConfig;

        $localStorage = new LocalStorage($filesStorage, $rootDir);

        $this->storageMap = [
            'local' => [
                'object' => $localStorage,
                'initialized' => false
            ]
        ];

    }

    /**
     * @param string $currentStorage
     * @return BaseStorage
     * @throws \Exception
     */
    private function getStorage(string $currentStorage = 'local'): BaseStorage
    {
        $object = $this->storageMap[$currentStorage]['object'];
        if (!$object instanceof BaseStorage) {
            throw new \Exception('invalid object type');
        }

        if ($this->storageMap[$currentStorage]['initialized'] === false) {

            $storageConfig = null;
            if (\is_array($this->storageConfig) && \count($this->storageConfig) > 0) {
                $storageConfig = $this->storageConfig;
            }

            $object->initialize($storageConfig);
            $this->storageMap[$currentStorage]['initialized'] = true;

        }

        return $object;

    }

    /**
     * @param string $currentStorage
     * @return string
     * @throws \Exception
     */
    public function getRootDir(string $currentStorage = 'local'): string
    {
        return $this->getStorage($currentStorage)->getRootDir();
    }

    /**
     * @param string $currentStorage
     * @return string
     * @throws \Exception
     */
    public function getPublicDir(string $currentStorage = 'local'): string
    {
        return $this->getStorage($currentStorage)->getPublicDir();
    }

    /**
     * @param mixed $strUuid
     * @param string $currentStorage
     * @return StorageObject|null
     */
    public function get(
        mixed  $strUuid,
        string $currentStorage = 'local'
    ): ?StorageObject
    {
        try {
            return $this->getStorage($currentStorage)->get($strUuid);
        } catch (\Throwable) {
        }

        return null;

    }

    /**
     * @param mixed $strUuid
     * @param string $currentStorage
     * @return void
     */
    public function delete(
        mixed  $strUuid,
        string $currentStorage = 'local'
    ): void
    {
        try {
            $this->getStorage($currentStorage)->delete($strUuid);
        } catch (\Throwable) {
        }

    }

    /**
     * @param mixed $strUuid
     * @param string $currentStorage
     * @return bool
     */
    public function exists(
        mixed  $strUuid,
        string $currentStorage = 'local'
    ): bool
    {
        try {
            return $this->getStorage($currentStorage)->exists($strUuid);
        } catch (\Throwable) {
        }

        return false;

    }

    /**
     * @param string|null $localPath
     * @param string|null $remotePath
     * @param bool $override
     * @param string $currentStorage
     * @return StorageObject|null
     */
    public function deploy(
        ?string $localPath,
        ?string $remotePath,
        bool    $override,
        string  $currentStorage = 'local'
    ): ?StorageObject
    {
        try {
            return $this->getStorage($currentStorage)->deploy($localPath, $remotePath, $override);
        } catch (\Throwable) {
        }

        return null;

    }

    /**
     * @param mixed $strUuid
     * @param string $currentStorage
     * @return void
     */
    public function synchronize(
        mixed  $strUuid,
        string $currentStorage = 'local'
    ): void
    {
        try {
            $this->getStorage($currentStorage)->synchronize($strUuid);
        } catch (\Throwable) {
        }

    }

    /**
     * @param string $filePath
     * @param mixed $content
     * @param string $currentStorage
     * @return StorageObject|null
     * @throws \Exception
     */
    public function createFile(
        string $filePath,
        mixed  $content,
        string $currentStorage = 'local'
    ): ?StorageObject
    {
        try {
            return $this->getStorage($currentStorage)->createFile($filePath, $content);
        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

    /**
     * @param string $filePath
     * @param string $currentStorage
     * @return StorageObject|null
     * @throws \Exception
     */
    public function createDirectory(
        string $filePath,
        string $currentStorage = 'local'
    ): ?StorageObject
    {
        try {
            return $this->getStorage($currentStorage)->createDirectory($filePath);
        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

    /**
     * @param StorageObject $object
     * @param string $currentStorage
     * @return void
     * @throws \Exception
     */
    public function setMeta(
        StorageObject $object,
        string        $currentStorage = 'local'
    ): void
    {
        try {
            $this->getStorage($currentStorage)->setMeta($object);
        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

    /**
     * @param string $srcPath
     * @param string $destFileName
     * @param string $currentStorage
     * @return StorageObject|null
     * @throws \Exception
     */
    public function rename(
        string $srcPath,
        string $destFileName,
        string $currentStorage = 'local'
    ): ?StorageObject
    {
        try {
            return $this->getStorage($currentStorage)->rename($srcPath, StringUtil::sanitizeFileName($destFileName));
        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

    /**
     * @param string $srcPath
     * @param string $destPath
     * @param string $currentStorage
     * @return StorageObject|null
     * @throws \Exception
     */
    public function move(
        string $srcPath,
        string $destPath,
        string $currentStorage = 'local'
    ): ?StorageObject
    {
        try {
            return $this->getStorage($currentStorage)->move($srcPath, $destPath);
        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

    /**
     * @param string $srcPath
     * @param string $destPath
     * @param string $currentStorage
     * @return StorageObject|null
     * @throws \Exception
     */
    public function copy(
        string $srcPath,
        string $destPath,
        string $currentStorage = 'local'
    ): ?StorageObject
    {
        try {
            return $this->getStorage($currentStorage)->copy($srcPath, $destPath);
        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

    /**
     * @param string $path
     * @param string $currentStorage
     * @return array
     * @throws \Exception
     */
    public function listDir(string $path, string $currentStorage = 'local'): array
    {
        try {
            return $this->getStorage($currentStorage)->listDir($path);
        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }
    }

    /**
     * @param string $path
     * @return string
     * @throws \Exception
     */
    public function sanitizePath(string $path): string
    {
        $path = \preg_replace('/\pC/u', '', $path);

        if ($path === null) {
            throw new \Exception('The file name could not be sanitzied');
        }

        // Remove special characters not supported on e.g. Windows
        $path = \str_replace(array('\\', ':', '*', '?', '"', '<', '>', '|'), '-', $path);

        if (\str_contains($path, '..')) {
            throw new \Exception("invalid levelup sequence ..");
        }

        if (\str_contains($path, '~')) {
            throw new \Exception("invalid tilde sequence");
        }

        if (\str_starts_with($path, '/')) {
            $path = \substr($path, 1, \strlen($path));
        }

        if (\str_ends_with($path, '/')) {
            $path = \substr($path, 0, -1);
        }

        return $path;

    }

}