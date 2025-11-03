<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage;

use Alpdesk\AlpdeskCore\Library\Storage\Local\LocalStorage;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\Environment;
use Contao\StringUtil;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class StorageAdapter
{
    private string $rootDir;
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
        $this->rootDir = $rootDir;
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
     * @return string
     */
    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    /**
     * @return string
     */
    public function getPublicDir(): string
    {

        if ((new Filesystem())->exists($this->rootDir . '/web')) {
            return 'web';
        }

        return 'public';

    }


    /**
     * @param mixed $strUuid
     * @param string $currentStorage
     * @return StorageObject|null
     */
    public function findByUuid(
        mixed  $strUuid,
        string $currentStorage = 'local'
    ): ?StorageObject
    {
        try {
            return $this->getStorage($currentStorage)->findByUuid($strUuid);
        } catch (\Throwable) {

        }

        return null;

    }

    /**
     * @param string|null $path
     * @return string|null
     */
    public function generateLocalUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $publicDir = $this->getPublicDir() . '/';
        if (\str_starts_with($path, $publicDir)) {
            $path = \substr($path, \strlen($publicDir));
        }

        return Environment::get('base') . $path;

    }

    /**
     * @param mixed $strUuid
     * @param string $currentStorage
     * @return void
     */
    public function deleteByUuid(
        mixed  $strUuid,
        string $currentStorage = 'local'
    ): void
    {
        try {

            $this->getStorage($currentStorage)->deleteByUuid($strUuid);

        } catch (\Throwable) {

        }

    }

    /**
     * @param mixed $strUuid
     * @param string $currentStorage
     * @return bool
     */
    public function existsByUuid(
        mixed  $strUuid,
        string $currentStorage = 'local'
    ): bool
    {
        try {

            return $this->getStorage($currentStorage)->existsByUuid($strUuid);

        } catch (\Throwable) {

        }

        return false;

    }

    /**
     * @param mixed $uuid
     * @param bool $checkFileExists
     * @param string $currentStorage
     * @return string|null
     */
    public function uuidForDb(
        mixed  $uuid,
        bool   $checkFileExists = false,
        string $currentStorage = 'local'
    ): ?string
    {
        try {

            return $this->getStorage($currentStorage)->uuidForDb($uuid, $checkFileExists);

        } catch (\Exception) {

        }

        return null;

    }

    /**
     * @param string|null $bin
     * @param bool $checkFileExists
     * @param string $currentStorage
     * @return string|null
     */
    public function dbToUuid(
        ?string $bin,
        bool    $checkFileExists = false,
        string  $currentStorage = 'local'
    ): ?string
    {
        try {

            return $this->getStorage($currentStorage)->dbToUuid($bin, $checkFileExists);

        } catch (\Exception) {

        }

        return null;

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
     * @return array
     */
    public function listDir(string $path): array
    {
        $arrReturn = [];

        $filesystem = new Filesystem();

        if (!$filesystem->exists($path) || !\is_dir($path)) {
            return $arrReturn;
        }

        $finderDirs = (new Finder())
            ->in($path)
            ->depth('== 0')
            ->directories()
            ->sortByName();

        foreach ($finderDirs as $dir) {
            $arrReturn[] = $dir->getFilename();
        }

        $finderFiles = (new Finder())
            ->in($path)
            ->depth('== 0')
            ->files()
            ->sortByName();

        foreach ($finderFiles as $file) {
            $arrReturn[] = $file->getFilename();
        }

        return $arrReturn;

    }

    /**
     * @param string $path
     * @return string
     * @throws \Exception
     */
    public function sanitizePath(string $path): string
    {
        $path = \preg_replace('/[\pC]/u', '', $path);

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