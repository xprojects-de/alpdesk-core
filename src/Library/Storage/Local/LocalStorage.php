<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage\Local;

use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;
use Alpdesk\AlpdeskCore\Library\Storage\BaseStorageInterface;
use Alpdesk\AlpdeskCore\Library\Storage\StorageObject;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\Dbafs;
use Contao\Environment;
use Contao\File;
use Contao\FilesModel;
use Contao\Folder;
use Contao\StringUtil;
use Contao\Validator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class LocalStorage implements BaseStorageInterface
{
    private VirtualFilesystemInterface $filesStorage;
    private string $rootDir;
    private bool $onlyUseSyncedFiles;

    protected ?AlpdescCoreBaseMandantInfo $mandant = null;

    /**
     * @param VirtualFilesystemInterface $filesStorage
     * @param string $rootDir
     * @param bool $onlyUseSyncedFiles
     */
    public function __construct(
        VirtualFilesystemInterface $filesStorage,
        string                     $rootDir,
        bool                       $onlyUseSyncedFiles
    )
    {
        $this->filesStorage = $filesStorage;
        $this->rootDir = $rootDir;
        $this->onlyUseSyncedFiles = $onlyUseSyncedFiles;
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
     * @return StorageObject|null
     */
    public function get(mixed $strUuid): ?StorageObject
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

                        if ($file->isUnprotected()) {
                            $storageObject->url = $this->generateLocalUrl($file->path);
                        }

                        $storageObject->uuid = self::binToUuid($fileObject->uuid);
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
                    $storageObject->uuid = self::binToUuid($fileObject->uuid);
                    $storageObject->type = 'folder';
                    $storageObject->isPublic = $folder->isUnprotected();

                    return $storageObject;

                }

            }

            // Maybe it's a local file without database sync
            if (
                $isPath === true &&
                $this->onlyUseSyncedFiles === false &&
                (new Filesystem())->exists($this->rootDir . '/' . $strUuid)
            ) {

                if (\is_file($this->rootDir . '/' . $strUuid)) {

                    $objectFile = new File($strUuid);

                    $storageObject = new StorageObject();

                    $storageObject->path = $objectFile->path;
                    $storageObject->basename = $objectFile->basename;
                    $storageObject->extension = $objectFile->extension;
                    $storageObject->absolutePath = $this->rootDir . '/' . $objectFile->path;
                    $storageObject->name = $objectFile->name;
                    $storageObject->filename = $objectFile->filename;

                    if ($objectFile->isUnprotected()) {
                        $storageObject->url = $this->generateLocalUrl($objectFile->path);
                    }

                    $storageObject->uuid = null;
                    $storageObject->type = 'file';
                    $storageObject->meta = null;
                    $storageObject->isPublic = $objectFile->isUnprotected();
                    $storageObject->size = $objectFile->size;
                    $storageObject->isImage = $objectFile->isImage;

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
                    $storageObject->uuid = null;
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
     * @throws \Exception
     */
    public function delete(mixed $strUuid): void
    {
        $fileObject = $this->get($strUuid);
        if ($fileObject instanceof StorageObject) {

            if ($fileObject->type === 'file') {

                $f = new File($fileObject->path);
                if ($f->exists()) {
                    $f->delete();
                }

            } else if ($fileObject->type === 'folder') {

                $f = new Folder($fileObject->path);
                $f->delete();

            } else {
                throw new \Exception('file type not supported');
            }

            // not working because of Contao DBFS sync also for not synchronized folders
            /*
            if ($fileObject->type === 'file') {
                $this->filesStorage->delete(\str_replace('files/', '', $fileObject->path));
            } else if ($fileObject->type === 'folder') {
                $this->filesStorage->deleteDirectory(\str_replace('files/', '', $fileObject->path));
            } else {
                throw new \Exception('file type not supported');
            }
            */

        }


    }

    /**
     * @param mixed $strUuid
     * @return bool
     */
    public function exists(mixed $strUuid): bool
    {
        $fileObject = $this->get($strUuid);
        return ($fileObject !== null);
    }

    /**
     * @param string|null $localPath
     * @param string|null $remotePath
     * @param bool $override
     * @return StorageObject|null
     * @throws \Exception
     */
    public function deploy(?string $localPath, ?string $remotePath, bool $override): ?StorageObject
    {
        throw new \Exception("not supported");
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
        $this->write($filePath, $content);
        return $this->get($filePath);

    }

    /**
     * @param string $filePath
     * @return StorageObject|null
     * @throws \Exception
     */
    public function createDirectory(string $filePath): ?StorageObject
    {
        // not working because of Contao DBFS sync also for not synchronized folders
        // $this->filesStorage->createDirectory(\str_replace('files/', '', $filePath));

        $folder = new Folder($filePath);
        return $this->get($folder->path);
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
        $srcObject = $this->get($srcPath);
        if (!$srcObject instanceof StorageObject) {
            return null;
        }

        $parent = \substr($srcObject->path, 0, (\strlen($srcObject->path) - \strlen($srcObject->basename)));

        if ($this->exists($parent . $destFileName)) {
            throw new \Exception("target still exists");
        }

        if ($srcObject->type === 'file') {

            (new File($srcObject->path))->renameTo($parent . $destFileName);
            return $this->get($parent . $destFileName);

        }

        if ($srcObject->type === 'folder') {

            (new Folder($srcObject->path))->renameTo($parent . $destFileName);
            return $this->get($parent . $destFileName);

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
        return $this->copyMove($srcPath, $destPath, true);
    }

    /**
     * @param string $srcPath
     * @param string $destPath
     * @return StorageObject|null
     * @throws \Exception
     */
    public function copy(string $srcPath, string $destPath): ?StorageObject
    {
        return $this->copyMove($srcPath, $destPath);
    }

    /**
     * @param string $srcPath
     * @param string $destPath
     * @param bool $isMove
     * @return StorageObject|null
     * @throws \Exception
     */
    private function copyMove(string $srcPath, string $destPath, bool $isMove = false): ?StorageObject
    {
        $srcObject = $this->get($srcPath);
        if (!$srcObject instanceof StorageObject) {
            throw new \Exception('src file/folder to move not found.');
        }

        $destObject = $this->get($destPath);
        if (!$destObject instanceof StorageObject) {
            throw new \Exception('dest file/folder to move not found.');
        }

        if ($srcObject->type === 'file') {

            $fileLocal = new File($srcObject->path);
            if (!$fileLocal->exists()) {
                throw new \Exception('src file/folder to move not found.');
            }

            $remoteFile = $destObject->path . '/' . $fileLocal->name;

            $fileRemoteCheck = new File($remoteFile);
            if ($fileRemoteCheck->exists()) {
                $remoteFile = \str_replace('.' . $fileRemoteCheck->extension, '_' . \time() . '.' . $fileRemoteCheck->extension, $remoteFile);
            }

            if ($fileLocal->copyTo($remoteFile) === false) {
                throw new \Exception('error on move file.');
            }

            $fileRemote = new File($remoteFile);
            if (!$fileRemote->exists()) {
                throw new \Exception('error on move file.');
            }

            if ($isMove) {
                $fileLocal->delete();
            }

            return $this->get($remoteFile);

        }

        if ($srcObject->type === 'folder') {

            $localFolder = new Folder($srcObject->path);
            $remoteFolder = new Folder($destObject->path);

            $remoteFolderPath = $remoteFolder->path . '/' . $localFolder->name;

            if ($localFolder->copyTo($remoteFolderPath) === false) {
                throw new \Exception('error on move folder.');
            }

            if ($isMove) {
                $localFolder->delete();
            }

            return $this->get($remoteFolderPath);

        }

        throw new \Exception('file type not supported');

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
     * @param mixed $contents
     * @param string $path
     * @return void
     * @throws \Exception
     */
    public function write(mixed $contents, string $path): void
    {
        $file = new File($path);

        if ($file->exists()) {

            $newFilePath = \str_replace('.' . $file->extension, '_' . \time() . '.' . $file->extension, $file->path);
            $file = new File($newFilePath);
        }

        $file->write($contents);
        $file->close();

        if (!$file->exists()) {
            throw new \Exception('File could not be written');
        }

        // Cannot be used because it is sync the files wth DBFS also for not synchronized folders
        // $this->filesStorage->write(\str_replace('files/', '', $path), $contents);
    }

    /**
     * @param string|null $bin
     * @return string|null
     */
    private static function binToUuid(?string $bin): ?string
    {
        try {

            if ($bin === null || $bin === '') {
                return null;
            }

            if (Validator::isStringUuid($bin)) {
                return $bin;
            }

            $uuid = StringUtil::binToUuid($bin);

            if (!Validator::isStringUuid($uuid)) {
                return null;
            }

            return $uuid;

        } catch (\Exception) {
        }

        return null;

    }

    /**
     * @param AlpdescCoreBaseMandantInfo|null $mandant
     * @return void
     */
    public function addMandant(?AlpdescCoreBaseMandantInfo $mandant): void
    {
        $this->mandant = $mandant;
    }

}