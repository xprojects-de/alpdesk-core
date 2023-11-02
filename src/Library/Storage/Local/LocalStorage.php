<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage\Local;

use Alpdesk\AlpdeskCore\Library\Storage\BaseStorage;
use Alpdesk\AlpdeskCore\Library\Storage\StorageObject;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\Dbafs;
use Contao\File;
use Contao\FilesModel;
use Contao\Folder;
use Contao\StringUtil;
use Contao\Validator;
use Symfony\Component\Filesystem\Filesystem;

class LocalStorage extends BaseStorage
{
    private VirtualFilesystemInterface $filesStorage;

    /**
     * @param VirtualFilesystemInterface $filesStorage
     * @param string $rootDir
     */
    public function __construct(VirtualFilesystemInterface $filesStorage, string $rootDir)
    {
        parent::__construct($rootDir);
        $this->filesStorage = $filesStorage;
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

            if (Validator::isStringUuid($strUuid)) {
                $strUuid = StringUtil::uuidToBin($strUuid);
            }

            $isPath = false;
            if (!Validator::isBinaryUuid($strUuid) && !Validator::isStringUuid($strUuid)) {
                $isPath = true;
            }

            if ($isPath === true) {
                $fileObject = FilesModel::findByPath($strUuid);
            } else {
                $fileObject = FilesModel::findByUuid($strUuid);
            }

            if ($fileObject !== null) {

                if ($fileObject->type === 'file') {

                    $file = new File($fileObject->path);
                    if ($file->exists()) {

                        $storageObject = new StorageObject();

                        $storageObject->path = $fileObject->path;
                        $storageObject->absolutePath = $fileObject->getAbsolutePath();
                        $storageObject->name = $fileObject->name;
                        $storageObject->filename = $file->filename;
                        $storageObject->url = $this->generateLocalUrl($fileObject->path);
                        $storageObject->uuid = self::binToUuid($fileObject->uuid);
                        $storageObject->file = $file;

                        return $storageObject;

                    }

                } else if ($fileObject->type === 'folder') {

                    $folder = new Folder($fileObject->path);

                    $storageObject = new StorageObject();

                    $storageObject->path = $fileObject->path;
                    $storageObject->absolutePath = $fileObject->getAbsolutePath();
                    $storageObject->name = $fileObject->name;
                    $storageObject->filename = $folder->filename;
                    $storageObject->url = $this->generateLocalUrl($fileObject->path);
                    $storageObject->uuid = self::binToUuid($fileObject->uuid);
                    $storageObject->folder = $folder;

                    return $storageObject;

                }

            }

            // Maybe it´s a local file without database sync
            if ($isPath === true && (new Filesystem())->exists($this->rootDir . '/' . $strUuid)) {

                if (\is_file($this->rootDir . '/' . $strUuid)) {

                    $objectFile = new File($strUuid);

                    $storageObject = new StorageObject();

                    $storageObject->path = $objectFile->path;
                    $storageObject->absolutePath = $this->rootDir . '/' . $objectFile->path;
                    $storageObject->name = $objectFile->name;
                    $storageObject->filename = $objectFile->filename;
                    $storageObject->url = $this->generateLocalUrl($objectFile->path);
                    $storageObject->uuid = null;
                    $storageObject->file = $objectFile;

                    return $storageObject;

                }

                if (\is_dir($this->rootDir . '/' . $strUuid)) {

                    $objectFolder = new Folder($strUuid);

                    $storageObject = new StorageObject();

                    $storageObject->path = $objectFolder->path;
                    $storageObject->absolutePath = $this->rootDir . '/' . $objectFolder->path;
                    $storageObject->name = $objectFolder->name;
                    $storageObject->filename = $objectFolder->filename;
                    $storageObject->url = $this->generateLocalUrl($objectFolder->path);
                    $storageObject->uuid = null;
                    $storageObject->folder = $objectFolder;

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
        $fileObject = $this->findByUuid($strUuid);
        if ($fileObject !== null) {

            if ($fileObject->file !== null) {
                $fileObject->file->delete();
            } else if ($fileObject->folder !== null) {
                $fileObject->folder->delete();
            }

        }
    }

    /**
     * @param mixed $strUuid
     * @return bool
     */
    public function existsByUuid(mixed $strUuid): bool
    {
        $fileObject = $this->findByUuid($strUuid);
        return ($fileObject !== null);
    }

    /**
     * @param mixed $uuid
     * @param bool $checkFileExists
     * @return string|null
     */
    public function uuidForDb(mixed $uuid, bool $checkFileExists): ?string
    {
        try {

            if ($uuid === null || $uuid === '') {
                return null;
            }

            if (Validator::isBinaryUuid($uuid)) {
                return $uuid;
            }

            if (Validator::isStringUuid($uuid) === false) {
                return null;
            }

            if ($checkFileExists === true) {

                $check = $this->existsByUuid($uuid);
                if ($check === false) {
                    return null;
                }

            }

            return StringUtil::uuidToBin($uuid);

        } catch (\Exception $ex) {

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

            if ($bin === null || $bin === '') {
                return null;
            }

            if (Validator::isStringUuid($bin)) {
                return $bin;
            }

            $uuid = StringUtil::binToUuid($bin);

            if (Validator::isStringUuid($uuid) === false) {
                return null;
            }

            if ($checkFileExists === true) {

                $check = $this->existsByUuid($uuid);
                if ($check === false) {
                    return null;
                }

            }

            return $uuid;

        } catch (\Exception $ex) {

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

            $storageObject = new StorageObject();

            if ($localPath === $remotePath) {

                $model = $fileLocal->getModel();

                $storageObject->path = $fileLocal->path;
                $storageObject->absolutePath = $this->rootDir . '/' . $fileLocal->path;
                $storageObject->name = $fileLocal->name;
                $storageObject->filename = $fileLocal->filename;
                $storageObject->url = $this->generateLocalUrl($fileLocal->path);
                $storageObject->uuid = ($model !== null ? self::binToUuid($model->uuid) : null);
                $storageObject->file = $fileLocal;

            } else {

                if ($fileLocal->copyTo($remotePath) === false) {
                    return null;
                }

                $fileLocal->delete();

                $fileRemote = new File($remotePath);
                if (!$fileRemote->exists()) {
                    return null;
                }

                $model = $fileRemote->getModel();

                $storageObject->path = $fileRemote->path;
                $storageObject->absolutePath = $this->rootDir . '/' . $fileRemote->path;
                $storageObject->name = $fileRemote->name;
                $storageObject->filename = $fileRemote->filename;
                $storageObject->url = $this->generateLocalUrl($fileRemote->path);
                $storageObject->uuid = ($model !== null ? self::binToUuid($model->uuid) : null);
                $storageObject->file = $fileRemote;

            }

            return $storageObject;


        } catch (\Throwable $tr) {

        }

        return null;

    }

    /**
     * @param mixed $strUuid
     * @return void
     * @throws \Exception
     */
    public function synchronize(mixed $strUuid): void
    {
        if (
            \is_string($strUuid) &&
            $strUuid !== ''
        ) {

            if (Dbafs::shouldBeSynchronized($strUuid)) {
                Dbafs::addResource($strUuid);
            }

        }

    }

}