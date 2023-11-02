<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage;

use Alpdesk\AlpdeskCore\Library\Storage\Local\LocalStorage;
use Contao\File;
use Contao\Validator;
use League\Flysystem\Filesystem;

abstract class GenericFlysystemStorage extends BaseStorage
{
    protected ?Filesystem $filesystem = null;
    protected LocalStorage $localStorage;

    /**
     * @param string $rootDir
     * @param LocalStorage $localStorage
     */
    public function __construct(string $rootDir, LocalStorage $localStorage)
    {
        parent::__construct($rootDir);
        $this->localStorage = $localStorage;
    }

    /**
     * @param mixed $strUuid
     * @return File|null
     */
    private function downloadObject(mixed $strUuid): ?File
    {
        try {

            $pathInfo = $this->getPathInfo($strUuid);

            $basePath = $this->getPublicDir() . '/tmp/' . $pathInfo['filename'] . '_' . \time() . '.' . $pathInfo['extension'];
            $tmpPath = $this->rootDir . '/' . $basePath;

            $readResource = $this->filesystem->readStream($strUuid);
            $downloadFile = \fopen($tmpPath, 'wb');
            \fwrite($downloadFile, \stream_get_contents($readResource));
            \fclose($downloadFile);
            \fclose($readResource);

            $downloadFinalFile = new File($basePath);
            if ($downloadFinalFile->exists()) {
                return $downloadFinalFile;
            }

        } catch (\Throwable $tr) {

        }

        return null;

    }

    /**
     * @param array|null $config
     * @return void
     */
    public function initialize(?array $config): void
    {

    }

    /**
     * @param mixed $strUuid
     * @return StorageObject|null
     */
    public function findByUuid(mixed $strUuid): ?StorageObject
    {
        try {

            if (!\is_string($strUuid)) {
                return null;
            }

            if ($strUuid === '') {
                return null;
            }

            if (Validator::isBinaryUuid($strUuid) || Validator::isStringUuid($strUuid)) {
                return $this->localStorage->findByUuid($strUuid);
            }

            if (!$this->filesystem instanceof Filesystem) {
                throw new \Exception('invalid Filesystem instance');
            }

            if ($this->existsByUuid($strUuid) === true) {

                $downloadFile = $this->downloadObject($strUuid);
                if ($downloadFile instanceof File) {

                    $pathInfo = $this->getPathInfo($strUuid);

                    $storageObject = new StorageObject();

                    $storageObject->path = $strUuid;
                    $storageObject->absolutePath = $strUuid;
                    $storageObject->name = $pathInfo['basename'];
                    $storageObject->filename = $pathInfo['filename'];
                    $storageObject->url = $this->filesystem->temporaryUrl($strUuid, (new \DateTime())->modify('+15 minutes'));
                    $storageObject->uuid = null;
                    $storageObject->file = $downloadFile;

                    return $storageObject;

                }

            }

        } catch (\Throwable $tr) {

        }

        return null;

    }

    /**
     * @param mixed $strUuid
     * @return void
     */
    public function deleteByUuid(mixed $strUuid): void
    {
        try {

            if (!$this->filesystem instanceof Filesystem) {
                throw new \Exception('invalid Filesystem instance');
            }

            if (Validator::isBinaryUuid($strUuid) || Validator::isStringUuid($strUuid)) {
                $this->localStorage->deleteByUuid($strUuid);
            } else if (\is_string($strUuid) && $strUuid !== '' && $this->filesystem->has($strUuid)) {
                $this->filesystem->delete($strUuid);
            }

        } catch (\Throwable $tr) {

        }

    }

    /**
     * @param mixed $strUuid
     * @return bool
     */
    public function existsByUuid(mixed $strUuid): bool
    {
        try {

            if (!\is_string($strUuid)) {
                return false;
            }

            if ($strUuid === '') {
                return false;
            }

            if (Validator::isBinaryUuid($strUuid) || Validator::isStringUuid($strUuid)) {
                return $this->localStorage->existsByUuid($strUuid);
            }

            if (!$this->filesystem instanceof Filesystem) {
                throw new \Exception('invalid Filesystem instance');
            }

            return $this->filesystem->has($strUuid);

        } catch (\Throwable $tr) {

        }

        return false;

    }

    /**
     * @param mixed $uuid
     * @param bool $checkFileExists
     * @return string|null
     */
    public function uuidForDb(mixed $uuid, bool $checkFileExists): ?string
    {
        try {

            if (!\is_string($uuid)) {
                return null;
            }

            if ($uuid === '') {
                return null;
            }

            if (Validator::isBinaryUuid($uuid) || Validator::isStringUuid($uuid)) {
                return $this->localStorage->uuidForDb($uuid, $checkFileExists);
            }

            if ($checkFileExists === true) {

                $check = $this->existsByUuid($uuid);
                if ($check === false) {
                    return null;
                }

            }

            return $uuid;

        } catch (\Throwable $tr) {

        }

        return null;

    }

    /**
     * @param string|null $bin
     * @param bool $checkFileExists
     * @return string|null
     */
    public function dbToUuid(?string $bin, bool $checkFileExists): ?string
    {
        try {

            if (!\is_string($bin)) {
                return null;
            }

            if ($bin === '') {
                return null;
            }

            if (Validator::isBinaryUuid($bin) || Validator::isStringUuid($bin)) {
                return $this->localStorage->dbToUuid($bin, $checkFileExists);
            }

            if ($checkFileExists === true) {

                $check = $this->existsByUuid($bin);
                if ($check === false) {
                    return null;
                }

            }

            return $bin;

        } catch (\Throwable $tr) {

        }

        return null;

    }

    /**
     * @param string|null $localPath
     * @param string|null $remotePath
     * @return StorageObject|null
     */
    public function deploy(?string $localPath, ?string $remotePath): ?StorageObject
    {
        try {

            if (!\is_string($localPath) || !\is_string($remotePath)) {
                return null;
            }

            if ($localPath === '' || $remotePath === '') {
                return null;
            }

            $fileLocal = new File($localPath);
            if (!$fileLocal->exists()) {
                return null;
            }

            if ($localPath === $remotePath) {

                $storageObject = new StorageObject();

                $storageObject->path = $fileLocal->path;
                $storageObject->absolutePath = $this->rootDir . '/' . $fileLocal->path;
                $storageObject->name = $fileLocal->name;
                $storageObject->filename = $fileLocal->filename;
                $storageObject->url = $this->generateLocalUrl($fileLocal->path);
                $storageObject->uuid = null;
                $storageObject->file = $fileLocal;

                return $storageObject;
            }

            if (!$this->filesystem instanceof Filesystem) {
                throw new \Exception('invalid Filesystem instance');
            }

            if (Validator::isBinaryUuid($localPath) || Validator::isStringUuid($localPath)) {
                throw new \Exception('invalid path');
            }

            $localFileSystem = new \Symfony\Component\Filesystem\Filesystem();
            $completePath = $this->rootDir . '/' . $localPath;
            if ($localFileSystem->exists($completePath)) {

                if (\is_file($completePath)) {

                    $stream = \fopen($completePath, 'rb+');
                    $this->filesystem->writeStream($remotePath, $stream);
                    \fclose($stream);

                } else if (\is_dir($completePath)) {
                    $this->filesystem->createDirectory($remotePath);
                }

                return $this->findByUuid($remotePath);

            }

        } catch (\Throwable $tr) {

        }

        return null;

    }

    public function synchronize(mixed $strUuid): void
    {

    }

}