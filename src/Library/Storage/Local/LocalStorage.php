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
                        $storageObject->basename = $file->basename;
                        $storageObject->extension = $file->extension;
                        $storageObject->absolutePath = $fileObject->getAbsolutePath();
                        $storageObject->name = $fileObject->name;
                        $storageObject->filename = $file->filename;
                        $storageObject->url = $this->generateLocalUrl($fileObject->path);
                        $storageObject->uuid = self::binToUuid($fileObject->uuid);
                        $storageObject->file = $file;
                        $storageObject->type = 'file';
                        $storageObject->meta = $fileObject->meta;
                        $storageObject->isPublic = $file->isUnprotected();
                        $storageObject->size = $file->size;
                        $storageObject->isImage = ($this->filesStorage->get(\str_replace('files/', '', $file->path))?->isImage() ?? false);

                        return $storageObject;

                    }

                } else if ($fileObject->type === 'folder') {

                    $folder = new Folder($fileObject->path);

                    $storageObject = new StorageObject();

                    $storageObject->path = $fileObject->path;
                    $storageObject->basename = $folder->basename;
                    $storageObject->absolutePath = $fileObject->getAbsolutePath();
                    $storageObject->name = $fileObject->name;
                    $storageObject->filename = $folder->filename;
                    $storageObject->url = $this->generateLocalUrl($fileObject->path);
                    $storageObject->uuid = self::binToUuid($fileObject->uuid);
                    $storageObject->folder = $folder;
                    $storageObject->type = 'folder';
                    $storageObject->isPublic = $folder->isUnprotected();

                    return $storageObject;

                }

            }

            // Maybe it's a local file without database sync
            if ($isPath === true && (new Filesystem())->exists($this->rootDir . '/' . $strUuid)) {

                if (\is_file($this->rootDir . '/' . $strUuid)) {

                    $objectFile = new File($strUuid);

                    $storageObject = new StorageObject();

                    $storageObject->path = $objectFile->path;
                    $storageObject->basename = $objectFile->basename;
                    $storageObject->extension = $objectFile->extension;
                    $storageObject->absolutePath = $this->rootDir . '/' . $objectFile->path;
                    $storageObject->name = $objectFile->name;
                    $storageObject->filename = $objectFile->filename;
                    $storageObject->url = $this->generateLocalUrl($objectFile->path);
                    $storageObject->uuid = null;
                    $storageObject->file = $objectFile;
                    $storageObject->type = 'file';
                    $storageObject->meta = $fileObject->meta;
                    $storageObject->isPublic = $objectFile->isUnprotected();
                    $storageObject->size = $objectFile->size;
                    $storageObject->isImage = ($this->filesStorage->get(\str_replace('files/', '', $objectFile->path))?->isImage() ?? false);

                    return $storageObject;

                }

                if (\is_dir($this->rootDir . '/' . $strUuid)) {

                    $objectFolder = new Folder($strUuid);

                    $storageObject = new StorageObject();

                    $storageObject->path = $objectFolder->path;
                    $storageObject->basename = $objectFolder->basename;
                    $storageObject->absolutePath = $this->rootDir . '/' . $objectFolder->path;
                    $storageObject->name = $objectFolder->name;
                    $storageObject->filename = $objectFolder->filename;
                    $storageObject->url = $this->generateLocalUrl($objectFolder->path);
                    $storageObject->uuid = null;
                    $storageObject->folder = $objectFolder;
                    $storageObject->type = 'folder';
                    $storageObject->isPublic = $objectFolder->isUnprotected();

                    return $storageObject;

                }

            }

        } catch (\Throwable) {
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
        if ($fileObject instanceof StorageObject) {

            if ($fileObject->file !== null) {
                $this->filesStorage->delete(\str_replace('files/', '', $fileObject->path));
            } else {
                $this->filesStorage->deleteDirectory(\str_replace('files/', '', $fileObject->path));
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

        } catch (\Exception) {
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

        } catch (\Exception) {
        }

        return null;
    }

    /**
     * @param string|null $localPath
     * @param string|null $remotePath
     * @param bool $override
     * @return StorageObject|null
     */
    public function deploy(?string $localPath, ?string $remotePath, bool $override): ?StorageObject
    {
        try {

            if (!\is_string($localPath) || !\is_string($remotePath)) {
                return null;
            }

            if ($localPath === '' || $remotePath === '') {
                return null;
            }

            if ($override === false) {

                $fileRemoteCheck = new File($remotePath);
                if ($fileRemoteCheck->exists()) {
                    $remotePath = \str_replace('.' . $fileRemoteCheck->extension, '_' . \time() . '.' . $fileRemoteCheck->extension, $remotePath);
                }

            }

            $fileLocal = new File($localPath);
            if (!$fileLocal->exists()) {
                return null;
            }

            $storageObject = new StorageObject();

            if ($localPath === $remotePath) {

                $model = $fileLocal->getModel();

                $storageObject->path = $fileLocal->path;
                $storageObject->basename = $fileLocal->basename;
                $storageObject->extension = $fileLocal->extension;
                $storageObject->absolutePath = $this->rootDir . '/' . $fileLocal->path;
                $storageObject->name = $fileLocal->name;
                $storageObject->filename = $fileLocal->filename;
                $storageObject->url = $this->generateLocalUrl($fileLocal->path);
                $storageObject->uuid = ($model !== null ? self::binToUuid($model->uuid) : null);
                $storageObject->file = $fileLocal;
                $storageObject->type = 'file';
                $storageObject->isPublic = $fileLocal->isUnprotected();
                $storageObject->size = $fileLocal->size;
                $storageObject->isImage = ($this->filesStorage->get(\str_replace('files/', '', $fileLocal->path))?->isImage() ?? false);

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
                $storageObject->basename = $fileRemote->basename;
                $storageObject->extension = $fileRemote->extension;
                $storageObject->absolutePath = $this->rootDir . '/' . $fileRemote->path;
                $storageObject->name = $fileRemote->name;
                $storageObject->filename = $fileRemote->filename;
                $storageObject->url = $this->generateLocalUrl($fileRemote->path);
                $storageObject->uuid = ($model !== null ? self::binToUuid($model->uuid) : null);
                $storageObject->file = $fileRemote;
                $storageObject->type = 'file';
                $storageObject->isPublic = $fileRemote->isUnprotected();
                $storageObject->size = $fileRemote->size;
                $storageObject->isImage = ($this->filesStorage->get(\str_replace('files/', '', $fileRemote->path))?->isImage() ?? false);

            }

            return $storageObject;

        } catch (\Throwable) {
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
            $strUuid !== '' &&
            Dbafs::shouldBeSynchronized($strUuid)
        ) {
            Dbafs::addResource($strUuid);
        }

    }

    /**
     * @param string $filePath
     * @param mixed $content
     * @return StorageObject|null
     * @throws \Exception
     */
    public function createFile(string $filePath, mixed $content): ?StorageObject
    {
        $this->filesStorage->write(\str_replace('files/', '', $filePath), $content);

        $storageObject = $this->deploy($filePath, $filePath, true);
        if ($storageObject instanceof StorageObject) {
            $this->synchronize($storageObject->uuid);
        }

        return $storageObject;

    }

    /**
     * @param string $filePath
     * @return StorageObject|null
     * @throws \Exception
     */
    public function createDirectory(string $filePath): ?StorageObject
    {
        $this->filesStorage->createDirectory(\str_replace('files/', '', $filePath));

        return $this->findByUuid($filePath);

    }

    /**
     * @param StorageObject $object
     * @return void
     * @throws \Exception
     */
    public function setMeta(StorageObject $object): void
    {
        $isPath = false;
        if (!Validator::isBinaryUuid($object->uuid) && !Validator::isStringUuid($object->uuid)) {
            $isPath = true;
        }

        if ($isPath === true) {
            $fileObject = FilesModel::findByPath($object->uuid);
        } else {
            $fileObject = FilesModel::findByUuid($object->uuid);
        }

        if ($fileObject === null) {
            throw new \Exception('File not found in database to set meta.');
        }

        $meta = $object->meta;
        if (\is_array($meta)) {
            $meta = serialize($meta);
        }

        $fileObject->meta = $meta;
        $fileObject->save();

    }

    /**
     * @param string $srcPath
     * @param string $destFileName
     * @return StorageObject|null
     * @throws \Exception
     */
    public function rename(string $srcPath, string $destFileName): ?StorageObject
    {
        $srcObject = $this->findByUuid($srcPath);
        if (!$srcObject instanceof StorageObject) {
            return null;
        }

        $parent = \substr($srcObject->path, 0, (\strlen($srcObject->path) - \strlen($srcObject->basename)));

        if ($this->existsByUuid($parent . $destFileName)) {
            throw new \Exception("target still exists");
        }

        if ($srcObject->type === 'file') {

            (new File($srcObject->path))->renameTo($parent . $destFileName);
            return $this->findByUuid($parent . $destFileName);

        }

        if ($srcObject->type === 'folder') {

            (new Folder($srcObject->path))->renameTo($parent . $destFileName);
            return $this->findByUuid($parent . $destFileName);

        }

        return null;

    }

    /**
     * @param string $srcPath
     * @param string $destPath
     * @return StorageObject|null
     * @throws \Exception
     */
    public function move(string $srcPath, string $destPath): ?StorageObject
    {
        return $this->deploy($srcPath, $destPath, false);
    }

    /**
     * @param string $srcPath
     * @param string $destPath
     * @return StorageObject|null
     * @throws \Exception
     */
    public function copy(string $srcPath, string $destPath): ?StorageObject
    {
        return $this->deploy($srcPath, $destPath, false);
    }

}