<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage;

use Alpdesk\AlpdeskCore\Library\Storage\AwsS3\AwsS3Storage;
use Alpdesk\AlpdeskCore\Library\Storage\Local\LocalStorage;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\Environment;
use Contao\System;
use Symfony\Component\Filesystem\Filesystem;

class StorageAdapter
{
    private string $rootDir;

    private array $storageMap;

    public static string $STORAGE_AWSS3 = 'awss3';

    public function __construct(
        VirtualFilesystemInterface $filesStorage,
        string                     $rootDir
    )
    {
        $this->rootDir = $rootDir;

        $localStorage = new LocalStorage($filesStorage, $rootDir);
        $awsS3Storage = new AwsS3Storage($rootDir, $localStorage);

        $this->storageMap = [
            'local' => [
                'object' => $localStorage,
                'initialized' => false
            ],
            'awss3' => [
                'object' => $awsS3Storage,
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

            try {
                $storageConfig = System::getContainer()->getParameter('alpdesk_core.storage');
            } catch (\Throwable $tr) {
            }

            if (!\is_array($storageConfig) || \count($storageConfig) <= 0) {
                $storageConfig = null;
            }

            $object->initialize($storageConfig);
            $this->storageMap[$currentStorage]['initialized'] = true;

        }

        return $object;

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

        } catch (\Throwable $tr) {

        }

        return null;

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

        } catch (\Throwable $tr) {

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

        } catch (\Throwable $tr) {

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

        } catch (\Exception $ex) {

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

        } catch (\Exception $ex) {

        }

        return null;

    }

    /**
     * @param string|null $localPath
     * @param string|null $remotePath
     * @param string $currentStorage
     * @return StorageObject|null
     */
    public function deploy(
        ?string $localPath,
        ?string $remotePath,
        string  $currentStorage = 'local'
    ): ?StorageObject
    {
        try {

            return $this->getStorage($currentStorage)->deploy($localPath, $remotePath);

        } catch (\Throwable $tr) {

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

        } catch (\Throwable $tr) {

        }

    }

}