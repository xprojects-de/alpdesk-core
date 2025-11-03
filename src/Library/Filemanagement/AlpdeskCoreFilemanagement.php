<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Filemanagement;

use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreFilemanagementFinderDeleteEvent;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreFilemanagementFinderListEvent;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreFilemanagementFinderMetaEvent;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreFilemanagementException;
use Alpdesk\AlpdeskCore\Library\Storage\StorageAdapter;
use Alpdesk\AlpdeskCore\Library\Storage\StorageObject;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Contao\File;
use Contao\StringUtil;
use Contao\Environment;
use Contao\Config;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;
use Contao\Validator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;

class AlpdeskCoreFilemanagement
{
    private AlpdeskCoreEventService $eventService;
    private StorageAdapter $storageAdapter;

    public function __construct(
        StorageAdapter          $storageAdapter,
        AlpdeskCoreEventService $eventService
    )
    {
        $this->storageAdapter = $storageAdapter;
        $this->eventService = $eventService;
    }

    /**
     * @param AlpdeskcoreUser $user
     * @return AlpdescCoreBaseMandantInfo
     * @throws AlpdeskCoreFilemanagementException
     */
    private function getMandantInformation(AlpdeskcoreUser $user): AlpdescCoreBaseMandantInfo
    {
        $mandantInfo = AlpdeskcoreMandantModel::findById($user->getMandantPid());

        if ($mandantInfo !== null) {

            $mInfo = new AlpdescCoreBaseMandantInfo();

            $rootPath = $this->storageAdapter->findByUuid($mandantInfo->filemount);

            $pathRootPath = $rootPath->path ?? '';

            $mInfo->setRootDir($this->storageAdapter->getRootDir());

            $mInfo->setFilemountmandant_uuid($mandantInfo->filemount);
            $mInfo->setFilemountmandant_path($pathRootPath);
            $mInfo->setFilemountmandant_rootpath($this->storageAdapter->getRootDir() . '/' . $pathRootPath);

            $mInfo->setFilemount_uuid($mandantInfo->filemount);
            $mInfo->setFilemount_path($pathRootPath);
            $mInfo->setFilemount_rootpath($this->storageAdapter->getRootDir() . '/' . $pathRootPath);

            if ($user->getHomeDir() !== null) {

                $rootPathMember = $this->storageAdapter->findByUuid($user->getHomeDir());
                if ($rootPathMember !== null) {
                    $mInfo->setFilemount_uuid($rootPathMember->uuid);
                    $mInfo->setFilemount_path($rootPathMember->path);
                    $mInfo->setFilemount_rootpath($this->storageAdapter->getRootDir() . '/' . $pathRootPath);
                }

            }

            $mInfo->setId((int)$mandantInfo->id);
            $mInfo->setMemberId($user->getMemberId());
            $mInfo->setMandant($mandantInfo->mandant);
            $mInfo->setAccessDownload($user->getAccessDownload());
            $mInfo->setAccessUpload($user->getAccessUpload());
            $mInfo->setAccessCreate($user->getAccessCreate());
            $mInfo->setAccessDelete($user->getAccessDelete());
            $mInfo->setAccessRename($user->getAccessRename());
            $mInfo->setAccessMove($user->getAccessMove());
            $mInfo->setAccessCopy($user->getAccessCopy());
            $mInfo->setAdditionalDatabaseInformation($mandantInfo->row());

            return $mInfo;

        }

        throw new AlpdeskCoreFilemanagementException("cannot get Mandantinformations", AlpdeskCoreConstants::$ERROR_INVALID_MANDANT);

    }

    /**
     * @param string|null $basePath
     * @param string $srcPath
     * @param AlpdescCoreBaseMandantInfo $mandantInfo
     * @throws AlpdeskCoreFilemanagementException
     */
    private function checkFileMountPermission(?string $basePath, string $srcPath, AlpdescCoreBaseMandantInfo $mandantInfo): void
    {
        if ($basePath === null) {

            $baseObject = $this->storageAdapter->findByUuid(StringUtil::binToUuid($mandantInfo->getFilemount_uuid()));
            if (!$baseObject instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("invalid mandant fileMount", AlpdeskCoreConstants::$ERROR_INVALID_PATH);
            }

            $basePath = $baseObject->path;

        }

        if (!\str_starts_with($srcPath, $basePath)) {
            throw new AlpdeskCoreFilemanagementException("invalid mandant fileMount - access denied", AlpdeskCoreConstants::$ERROR_INVALID_PATH);
        }

    }

    /**
     * @param UploadedFile $uploadFile
     * @param string $target
     * @param AlpdescCoreBaseMandantInfo $mandantInfo
     * @param AlpdeskCoreFileuploadResponse $response
     * @throws \Exception
     */
    private function copyToTarget(UploadedFile $uploadFile, string $target, AlpdescCoreBaseMandantInfo $mandantInfo, AlpdeskCoreFileuploadResponse $response): void
    {
        if ($mandantInfo->getAccessUpload() === false) {
            throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
        }

        $target = $this->storageAdapter->sanitizePath($target);

        if ($target === '/' || $target === '') {
            $target = StringUtil::binToUuid($mandantInfo->getFilemount_uuid());
        }

        $objTarget = $this->storageAdapter->findByUuid($target);
        if (!$objTarget instanceof StorageObject) {
            throw new AlpdeskCoreFilemanagementException("invalid target fileMount", AlpdeskCoreConstants::$ERROR_INVALID_PATH);
        }

        $this->checkFileMountPermission(null, $objTarget->path, $mandantInfo);

        $filesystem = new Filesystem();

        if ($filesystem->exists($uploadFile->getPathName())) {

            $fileName = $uploadFile->getClientOriginalName();
            try {
                $fileName = StringUtil::sanitizeFileName($fileName);
            } catch (\Exception $ex) {
                throw new AlpdeskCoreFilemanagementException($ex->getMessage());
            }

            $maxlength_kb = \min(UploadedFile::getMaxFilesize(), Config::get('maxFileSize'));
            $fileSize = $uploadFile->getSize();
            if ($fileSize > $maxlength_kb) {
                throw new AlpdeskCoreFilemanagementException('file is to large. max. ' . $maxlength_kb, AlpdeskCoreConstants::$ERROR_INVALID_INPUT);
            }

            $fileExt = \strtolower(\substr($fileName, \strrpos($fileName, '.') + 1));
            $allowedFileTypes = StringUtil::trimsplit(',', \strtolower(Config::get('uploadTypes')));
            if (!\in_array($fileExt, $allowedFileTypes, true)) {
                throw new AlpdeskCoreFilemanagementException('filetype ' . $fileExt . ' not allowed.', AlpdeskCoreConstants::$ERROR_INVALID_INPUT);
            }

            $tmpFileName = \time() . '_' . $fileName;
            $uploadFile->move($this->storageAdapter->getRootDir() . '/' . $objTarget->path, $tmpFileName);

            $tmpFile = new File($objTarget->path . '/' . $tmpFileName);
            if (!$tmpFile->exists()) {
                throw new AlpdeskCoreFilemanagementException("error upload file");
            }

            if ($filesystem->exists($mandantInfo->getRootDir() . '/' . $objTarget->path . '/' . $fileName)) {
                $fileName = time() . '_' . $fileName;
            }

            $tmpFile->renameTo($objTarget->path . '/' . $fileName);

            $nFile = new File($objTarget->path . '/' . $fileName);
            if (!$nFile->exists()) {
                throw new AlpdeskCoreFilemanagementException("error upload file");
            }

            $objnFile = $this->storageAdapter->findByUuid($objTarget->path . '/' . $fileName);
            if (!$objnFile instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("error upload file");
            }

            $response->setUuid(StringUtil::binToUuid($objnFile->uuid));
            $response->setRootFileName($objTarget->path . '/' . $fileName);
            $response->setFileName($nFile->basename);

        } else {
            throw new AlpdeskCoreFilemanagementException("error upload file");
        }

    }

    /**
     * @param string $target
     * @param AlpdescCoreBaseMandantInfo $mandantInfo
     * @return BinaryFileResponse
     * @throws AlpdeskCoreFilemanagementException
     */
    private function downloadFile(string $target, AlpdescCoreBaseMandantInfo $mandantInfo): BinaryFileResponse
    {
        if ($mandantInfo->getAccessDownload() === false) {
            throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
        }

        $target = $this->storageAdapter->sanitizePath($target);

        $objTarget = $this->storageAdapter->findByUuid($target);
        if (!$objTarget instanceof StorageObject) {
            throw new AlpdeskCoreFilemanagementException("invalid target fileMount");
        }

        $this->checkFileMountPermission(null, $objTarget->path, $mandantInfo);

        if ($objTarget->type === 'folder') {
            throw new AlpdeskCoreFilemanagementException("invalid src file - must be file");
        }

        $target = $objTarget->path;
        $pDest = $mandantInfo->getRootDir() . '/' . $target;

        $filesystem = new Filesystem();

        if ($filesystem->exists($pDest) && \is_file($pDest)) {

            $response = new BinaryFileResponse($pDest);
            $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, str_replace('/', '_', $target));

            return $response;

        }

        throw new AlpdeskCoreFilemanagementException("src-File not found on server");

    }

    /**
     * @param array $finderData
     * @param AlpdescCoreBaseMandantInfo $mandantInfo
     * @return array
     * @throws AlpdeskCoreFilemanagementException
     */
    private function listFolder(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo): array
    {
        try {

            $data = [];

            if (!\array_key_exists('src', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
            }

            $src = $this->storageAdapter->sanitizePath(((string)$finderData['src']));

            $objTargetBase = $this->storageAdapter->findByUuid($mandantInfo->getFilemount_uuid());
            if (!$objTargetBase instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("invalid Mandant filemount");
            }

            if (!Validator::isUuid($src)) {
                $src = $objTargetBase->path . '/' . $src;
            }

            $objTargetSrc = $this->storageAdapter->findByUuid($src);
            if (!$objTargetSrc instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("invalid src fileMount");
            }

            $this->checkFileMountPermission($objTargetBase->path, $objTargetSrc->path, $mandantInfo);

            if ($objTargetSrc->type !== 'folder') {
                throw new AlpdeskCoreFilemanagementException("invalid src folder - must be folder");
            }

            $listPath = $this->storageAdapter->getRootDir() . '/' . $objTargetSrc->path;

            $files = $this->storageAdapter->listDir($listPath);

            foreach ($files as $file) {

                $objFileTmp = $this->storageAdapter->findByUuid($objTargetSrc->path . '/' . $file);

                if ($objFileTmp instanceof StorageObject) {

                    if ($objFileTmp->type === 'folder') {

                        $data[] = [
                            'name' => $objFileTmp->basename,
                            'path' => $objFileTmp->path,
                            'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $objFileTmp->path),
                            'uuid' => $objFileTmp->uuid,
                            'extention' => '',
                            'public' => $objFileTmp->isPublic,
                            'url' => '',
                            'isFolder' => true,
                            'size' => '',
                            'isimage' => false
                        ];

                    } else if ($objFileTmp->type === 'file') {

                        $data[] = [
                            'name' => $objFileTmp->basename,
                            'path' => $objFileTmp->path,
                            'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $objFileTmp->path),
                            'uuid' => $objFileTmp->uuid,
                            'extention' => $objFileTmp->extension,
                            'public' => $objFileTmp->isPublic,
                            'url' => ($objFileTmp->url ?? ''),
                            'isFolder' => false,
                            'size' => $objFileTmp->size,
                            'isimage' => $objFileTmp->isImage
                        ];

                    }

                }

            }

            return $data;

        } catch (\Exception $ex) {
            throw new AlpdeskCoreFilemanagementException("error at listFolder - " . $ex->getMessage());
        }

    }

    /**
     * @param array $finderData
     * @param AlpdescCoreBaseMandantInfo $mandantInfo
     * @param bool $accessCheck
     * @return array
     * @throws AlpdeskCoreFilemanagementException
     */
    public function create(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): array
    {
        if ($accessCheck === true && $mandantInfo->getAccessCreate() === false) {
            throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
        }

        try {

            if (!\array_key_exists('src', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
            }

            $src = $this->storageAdapter->sanitizePath((string)$finderData['src']);

            if (!\array_key_exists('target', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
            }

            $target = $this->storageAdapter->sanitizePath((string)$finderData['target']);

            if ($target === "") {
                throw new AlpdeskCoreFilemanagementException("No valid mode in target. Must be 'file' or 'dir'");
            }

            $objTargetBase = $this->storageAdapter->findByUuid(StringUtil::binToUuid($mandantInfo->getFilemount_uuid()));
            if (!$objTargetBase instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("invalid Mandant FileMount");
            }

            $filesystem = new Filesystem();

            if ($filesystem->exists($mandantInfo->getRootDir() . '/' . $objTargetBase->path . '/' . $src)) {
                throw new AlpdeskCoreFilemanagementException("target still exists");
            }

            if ($target === 'file') {
                $this->storageAdapter->createFile($objTargetBase->path . '/' . $src, 'init');
            } else if ($target === 'dir') {
                $this->storageAdapter->createDirectory($objTargetBase->path . '/' . $src);
            } else {
                throw new AlpdeskCoreFilemanagementException("invalid targetMode");
            }

            $objTargetModel = $this->storageAdapter->findByUuid($objTargetBase->path . '/' . $src);
            if (!$objTargetModel instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("error create - target not found after create");
            }

            return [
                'uuid' => $objTargetModel->uuid,
                'path' => $objTargetModel->path,
                'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $objTargetModel->path)
            ];

        } catch (\Exception $ex) {
            throw new AlpdeskCoreFilemanagementException("error at create - " . $ex->getMessage());
        }

    }

    /**
     * @param array $finderData
     * @param AlpdescCoreBaseMandantInfo $mandantInfo
     * @param bool $accessCheck
     * @throws AlpdeskCoreFilemanagementException
     */
    public function delete(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): void
    {
        if ($accessCheck === true && $mandantInfo->getAccessDelete() === false) {
            throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
        }

        try {

            if (!\array_key_exists('src', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
            }

            $src = $this->storageAdapter->sanitizePath(((string)$finderData['src']));

            $objFileModelSrc = $this->storageAdapter->findByUuid($src);
            if (!$objFileModelSrc instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("src not found");
            }

            $this->checkFileMountPermission(null, $objFileModelSrc->path, $mandantInfo);

            if ($objFileModelSrc->type === 'folder' && $objFileModelSrc->folder !== null) {
                $objFileModelSrc->folder->delete();
            } else if ($objFileModelSrc->type === 'file' && $objFileModelSrc->file !== null) {
                $objFileModelSrc->file->delete();
            } else {
                throw new AlpdeskCoreFilemanagementException("error at copy - invalid source");
            }

        } catch (\Exception $ex) {
            throw new AlpdeskCoreFilemanagementException("error at delete - " . $ex->getMessage());
        }

    }

    /**
     * @param array $finderData
     * @param AlpdescCoreBaseMandantInfo $mandantInfo
     * @param bool $accessCheck
     * @return array
     * @throws AlpdeskCoreFilemanagementException
     */
    public function rename(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): array
    {
        if ($accessCheck === true && $mandantInfo->getAccessRename() === false) {
            throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
        }

        try {

            if (!\array_key_exists('src', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
            }

            $src = $this->storageAdapter->sanitizePath(((string)$finderData['src']));

            if ($src === '') {
                throw new AlpdeskCoreFilemanagementException("invalid src");
            }

            if (!\array_key_exists('target', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
            }

            $target = $this->storageAdapter->sanitizePath(((string)$finderData['target']));

            if ($target === '') {
                throw new AlpdeskCoreFilemanagementException("invalid target");
            }

            $objFileModelSrc = $this->storageAdapter->findByUuid($src);
            if (!$objFileModelSrc instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("src-File by uuid not found on server");
            }

            $this->checkFileMountPermission(null, $objFileModelSrc->path, $mandantInfo);

            $filesystem = new Filesystem();

            if ($objFileModelSrc->type === 'folder' && $objFileModelSrc->folder !== null) {

                $parent = \substr($objFileModelSrc->folder->path, 0, (\strlen($objFileModelSrc->folder->path) - \strlen($objFileModelSrc->folder->basename)));

                if ($filesystem->exists($mandantInfo->getRootDir() . '/' . $parent . $target)) {
                    throw new AlpdeskCoreFilemanagementException("target still exists");
                }

                $objFileModelSrc->folder->renameTo($parent . $target);

                $targetObject = $this->storageAdapter->findByUuid($parent . $target);
                if (!$targetObject instanceof StorageObject) {
                    throw new AlpdeskCoreFilemanagementException("error rename");
                }

                return [
                    'uuid' => $targetObject->uuid,
                    'path' => $targetObject->path,
                    'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $targetObject->path)
                ];

            }

            if ($objFileModelSrc->type === 'file' && $objFileModelSrc->file !== null) {

                $parent = \substr($objFileModelSrc->file->path, 0, (\strlen($objFileModelSrc->file->path) - \strlen($objFileModelSrc->file->basename)));

                if ($filesystem->exists($mandantInfo->getRootDir() . '/' . $parent . $target)) {
                    throw new AlpdeskCoreFilemanagementException("target still exists");
                }

                $objFileModelSrc->file->renameTo($parent . $target);

                $targetObject = $this->storageAdapter->findByUuid($parent . $target);
                if (!$targetObject instanceof StorageObject) {
                    throw new AlpdeskCoreFilemanagementException("error rename");
                }

                return [
                    'uuid' => $targetObject->uuid,
                    'path' => $targetObject->path,
                    'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $targetObject->path)
                ];

            }

            throw new AlpdeskCoreFilemanagementException("error at copy - invalid source");

        } catch (\Exception $ex) {
            throw new AlpdeskCoreFilemanagementException("error at rename - " . $ex->getMessage());
        }

    }

    /**
     * @param array $finderData
     * @param AlpdescCoreBaseMandantInfo $mandantInfo
     * @param bool $copy
     * @param bool $accessCheck
     * @return array
     * @throws AlpdeskCoreFilemanagementException
     */
    public function moveOrCopy(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $copy, bool $accessCheck = true): array
    {
        if ($copy === true) {

            if ($accessCheck === true && $mandantInfo->getAccessCopy() === false) {
                throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
            }
        } else {
            if ($accessCheck === true && $mandantInfo->getAccessMove() === false) {
                throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
            }
        }

        try {

            if (!\array_key_exists('src', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
            }

            $src = $this->storageAdapter->sanitizePath((string)$finderData['src']);

            if ($src === '') {
                throw new AlpdeskCoreFilemanagementException("invalid src");
            }

            if (!\array_key_exists('target', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
            }

            $target = $this->storageAdapter->sanitizePath((string)$finderData['target']);

            if ($target === '/' || $target === '') {
                $target = StringUtil::binToUuid($mandantInfo->getFilemount_uuid());
            }

            $objFileModelSrc = $this->storageAdapter->findByUuid($src);
            if (!$objFileModelSrc instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("src file by uuid not found");
            }

            $this->checkFileMountPermission(null, $objFileModelSrc->path, $mandantInfo);

            $objFileModelTarget = $this->storageAdapter->findByUuid($target);
            if (!$objFileModelTarget instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("src file by uuid not found");
            }

            $this->checkFileMountPermission(null, $objFileModelTarget->path, $mandantInfo);

            if ($objFileModelTarget->type !== 'folder') {
                throw new AlpdeskCoreFilemanagementException("error - target must be folder");
            }

            $filesystem = new Filesystem();

            if ($objFileModelSrc->type === 'folder' && $objFileModelSrc->folder !== null) {

                $basename = $objFileModelSrc->folder->basename;
                if ($filesystem->exists($mandantInfo->getRootDir() . '/' . $objFileModelTarget->path . '/' . $basename)) {
                    $basename = \time() . '_' . $basename;
                }

                if ($copy) {
                    $objFileModelSrc->folder->copyTo($objFileModelTarget->path . '/' . $basename);
                } else {
                    $objFileModelSrc->folder->renameTo($objFileModelTarget->path . '/' . $basename);
                }

                $targetObject = $this->storageAdapter->findByUuid($objFileModelTarget->path . '/' . $basename);
                if (!$targetObject instanceof StorageObject) {
                    throw new AlpdeskCoreFilemanagementException("error rename");
                }

                return [
                    'uuid' => $targetObject->uuid,
                    'name' => $basename,
                    'path' => $targetObject->path,
                    'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $targetObject->path)
                ];

            }

            if ($objFileModelSrc->type === 'file' && $objFileModelSrc->file !== null) {

                $basename = $objFileModelSrc->file->basename;
                if ($filesystem->exists($mandantInfo->getRootDir() . '/' . $objFileModelTarget->path . '/' . $basename)) {
                    $basename = \time() . '_' . $basename;
                }

                if ($copy) {
                    $objFileModelSrc->file->copyTo($objFileModelTarget->path . '/' . $basename);
                } else {
                    $objFileModelSrc->file->renameTo($objFileModelTarget->path . '/' . $basename);
                }

                $targetObject = $this->storageAdapter->findByUuid($objFileModelTarget->path . '/' . $basename);
                if (!$targetObject instanceof StorageObject) {
                    throw new AlpdeskCoreFilemanagementException("error rename");
                }

                return [
                    'uuid' => $targetObject->uuid,
                    'name' => $basename,
                    'path' => $targetObject->path,
                    'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $targetObject->path)
                ];

            }

            throw new AlpdeskCoreFilemanagementException("error at copy - invalid source");

        } catch (\Exception $ex) {
            throw new AlpdeskCoreFilemanagementException("error at moveOrCopy - " . $ex->getMessage());
        }

    }

    /**
     * @param array $finderData
     * @param AlpdescCoreBaseMandantInfo $mandantInfo
     * @param bool $accessCheck
     * @return array
     * @throws AlpdeskCoreFilemanagementException
     */
    public function meta(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): array
    {
        try {

            if (!\array_key_exists('src', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
            }

            $src = $this->storageAdapter->sanitizePath((string)$finderData['src']);

            if ($src === '') {
                throw new AlpdeskCoreFilemanagementException("invalid src");
            }

            $objFileModelSrc = $this->storageAdapter->findByUuid($src);
            if (!$objFileModelSrc instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("src file by uuid not found");
            }

            $this->checkFileMountPermission(null, $objFileModelSrc->path, $mandantInfo);

            if ($objFileModelSrc->type !== 'file' || $objFileModelSrc->file === null) {
                throw new AlpdeskCoreFilemanagementException("error - src must be file");
            }

            if (!$objFileModelSrc->file->exists()) {
                throw new AlpdeskCoreFilemanagementException("error - src file does not exists");
            }

            $url = '';
            $public = $objFileModelSrc->file->isUnprotected();
            if ($public === true) {
                $url = Environment::get('base') . $objFileModelSrc->file->path;
            }
            $basename = $objFileModelSrc->file->basename;
            $extension = $objFileModelSrc->file->extension;
            $size = $objFileModelSrc->file->size;
            $isImage = ($objFileModelSrc->file->isCmykImage || $objFileModelSrc->file->isGdImage || $objFileModelSrc->file->isImage || $objFileModelSrc->file->isRgbImage || $objFileModelSrc->file->isSvgImage);

            $metaData = [];

            if ($objFileModelSrc->meta !== null) {

                $metaDataTmp = StringUtil::deserialize($objFileModelSrc->meta);
                if (\is_array($metaDataTmp)) {
                    $metaData = $metaDataTmp;
                }

            }

            if (\array_key_exists('meta', $finderData)) {

                if ($accessCheck === true && $mandantInfo->getAccessCreate() === false) {
                    throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
                }

                $metaSet = ((array)$finderData['meta']);

                foreach ($metaSet as $key => $value) {

                    if (\is_string($key) && $key !== '' && \is_array($value)) {

                        foreach ($value as $valueKey => $valueValue) {

                            if ($valueKey === 'title' || $valueKey === 'alt' || $valueKey === 'link' || $valueKey === 'caption') {

                                if (!\array_key_exists($key, $metaData)) {
                                    $metaData[$key] = [];
                                }

                                $metaData[$key][$valueKey] = $valueValue;

                            }

                        }

                    }

                }

                $objFileModelSrc->meta = $metaData;
                $this->storageAdapter->setMeta($objFileModelSrc);

            }

            return [
                'uuid' => $objFileModelSrc->uuid,
                'name' => $basename,
                'path' => $objFileModelSrc->path,
                'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $objFileModelSrc->path),
                'extention' => $extension,
                'size' => $size,
                'isimage' => $isImage,
                'public' => $public,
                'url' => $url,
                'meta' => $metaData
            ];

        } catch (\Exception $ex) {
            throw new AlpdeskCoreFilemanagementException("error at meta - " . $ex->getMessage());
        }

    }

    /**
     * @param UploadedFile $uploadFile
     * @param string $target
     * @param AlpdeskcoreUser $user
     * @return AlpdeskCoreFileuploadResponse
     * @throws \Exception
     */
    public function upload(UploadedFile $uploadFile, string $target, AlpdeskcoreUser $user): AlpdeskCoreFileuploadResponse
    {
        $mandantInfo = $this->getMandantInformation($user);

        $response = new AlpdeskCoreFileuploadResponse();

        $this->copyToTarget($uploadFile, $target, $mandantInfo, $response);

        $response->setUsername($user->getUsername());
        $response->setAlpdesk_token($user->getUsedToken());
        $response->setMandantInfo($mandantInfo);

        return $response;
    }

    /**
     * @param AlpdeskcoreUser $user
     * @param array $downloadData
     * @return BinaryFileResponse
     * @throws AlpdeskCoreFilemanagementException
     */
    public function download(AlpdeskcoreUser $user, array $downloadData): BinaryFileResponse
    {
        if (!\array_key_exists('target', $downloadData)) {
            throw new AlpdeskCoreFilemanagementException("invalid key-parameters for download");
        }

        $target = (string)$downloadData['target'];
        $mandantInfo = $this->getMandantInformation($user);

        return $this->downloadFile($target, $mandantInfo);
    }

    /**
     * @param AlpdeskcoreUser $user
     * @param array $finderData
     * @return array|bool
     * @throws AlpdeskCoreFilemanagementException
     */
    public function finder(AlpdeskcoreUser $user, array $finderData): array|bool
    {
        if (!\array_key_exists('mode', $finderData)) {
            throw new AlpdeskCoreFilemanagementException("invalid key-parameter mode for finder");
        }

        $mode = (string)$finderData['mode'];

        $mandantInfo = $this->getMandantInformation($user);

        switch ($mode) {

            case 'list':
            {
                $finderResponseData = $this->listFolder($finderData, $mandantInfo);

                $event = new AlpdeskCoreFilemanagementFinderListEvent($finderData, $finderResponseData, $mandantInfo);
                $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreFilemanagementFinderListEvent::NAME);

                return $event->getResultData();

            }

            case 'create':
            {
                return $this->create($finderData, $mandantInfo);
            }

            case 'delete':
            {
                $this->delete($finderData, $mandantInfo);

                $event = new AlpdeskCoreFilemanagementFinderDeleteEvent($finderData, true, $mandantInfo);
                $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreFilemanagementFinderDeleteEvent::NAME);

                return $event->getResultData();

            }

            case 'rename':
            {
                return $this->rename($finderData, $mandantInfo);
            }

            case 'move':
            {
                return $this->moveOrcopy($finderData, $mandantInfo, false);
            }

            case 'copy':
            {
                return $this->moveOrcopy($finderData, $mandantInfo, true);
            }

            case 'meta':
            {
                $finderResponseData = $this->meta($finderData, $mandantInfo);

                $event = new AlpdeskCoreFilemanagementFinderMetaEvent($finderData, $finderResponseData, $mandantInfo);
                $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreFilemanagementFinderMetaEvent::NAME);

                return $event->getResultData();

            }

            default:
                throw new AlpdeskCoreFilemanagementException("invalid mode for finder", AlpdeskCoreConstants::$ERROR_INVALID_INPUT);

        }

    }

}
