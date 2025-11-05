<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Filemanagement;

use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreFileManagementRequestEvent;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreFilemanagementFinderDeleteEvent;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreFilemanagementFinderListEvent;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreFilemanagementFinderMetaEvent;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreFilemanagementException;
use Alpdesk\AlpdeskCore\Library\Storage\StorageAdapter;
use Alpdesk\AlpdeskCore\Library\Storage\StorageObject;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Contao\StringUtil;
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
    private string $storageType = 'local';

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
     * @throws \Exception
     */
    private function getMandantData(AlpdeskcoreUser $user): AlpdescCoreBaseMandantInfo
    {
        $mandantInfo = AlpdeskcoreMandantModel::findById($user->getMandantPid());

        if ($mandantInfo === null) {
            throw new AlpdeskCoreFilemanagementException("cannot get client info", AlpdeskCoreConstants::$ERROR_INVALID_MANDANT);
        }

        $mInfo = new AlpdescCoreBaseMandantInfo();

        $mInfo->setRootDir($this->storageAdapter->getRootDir($this->storageType));
        $mInfo->setFilemountmandant_uuid('');
        $mInfo->setFilemountmandant_path('');
        $mInfo->setFilemountmandant_rootpath($this->storageAdapter->getRootDir($this->storageType));
        $mInfo->setFilemount_uuid('');
        $mInfo->setFilemount_path('');
        $mInfo->setFilemount_rootpath($this->storageAdapter->getRootDir($this->storageType));

        if ($mandantInfo->filemount !== null && $mandantInfo->filemount !== '') {

            $mandantFileMount = $this->storageAdapter->get($mandantInfo->filemount, $this->storageType);
            if ($mandantFileMount instanceof StorageObject) {

                $mInfo->setFilemountmandant_uuid($mandantFileMount->uuid);
                $mInfo->setFilemountmandant_path($mandantFileMount->path);
                $mInfo->setFilemountmandant_rootpath($this->storageAdapter->getRootDir($this->storageType) . '/' . $mandantFileMount->path);

                $mInfo->setFilemount_uuid($mandantFileMount->uuid);
                $mInfo->setFilemount_path($mandantFileMount->path);
                $mInfo->setFilemount_rootpath($this->storageAdapter->getRootDir($this->storageType) . '/' . $mandantFileMount->path);

            }

        }

        if ($user->getHomeDir() !== null) {

            $rootPathMember = $this->storageAdapter->get($user->getHomeDir(), $this->storageType);
            if ($rootPathMember instanceof StorageObject) {

                $mInfo->setFilemount_uuid($rootPathMember->uuid);
                $mInfo->setFilemount_path($rootPathMember->path);
                $mInfo->setFilemount_rootpath($this->storageAdapter->getRootDir($this->storageType) . '/' . $rootPathMember->path);

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

    /**
     * @param string $src
     * @param AlpdescCoreBaseMandantInfo $mandantInfo
     * @param bool $checkPermission
     * @return string
     * @throws \Exception
     */
    private function prepareSrcPath(string $src, AlpdescCoreBaseMandantInfo $mandantInfo, bool $checkPermission): string
    {
        $src = $this->storageAdapter->sanitizePath($src);

        $objTargetBase = $this->storageAdapter->get($mandantInfo->getFilemount_uuid(), $this->storageType);
        if (!$objTargetBase instanceof StorageObject) {
            throw new AlpdeskCoreFilemanagementException("invalid Mandant fileMount");
        }

        if (!Validator::isUuid($src)) {
            $src = $objTargetBase->path . '/' . $src;
        }

        if ($checkPermission) {

            $objTargetSrc = $this->storageAdapter->get($src, $this->storageType);
            if (!$objTargetSrc instanceof StorageObject) {
                throw new \Exception("invalid src fileMount");
            }

            $this->storageAdapter->hasMountPermission($objTargetSrc->path, $objTargetBase->path, $this->storageType);

        }

        return $src;

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

        $target = $this->prepareSrcPath($target, $mandantInfo, true);

        $objTarget = $this->storageAdapter->get($target, $this->storageType);
        if (!$objTarget instanceof StorageObject) {
            throw new AlpdeskCoreFilemanagementException("invalid target fileMount", AlpdeskCoreConstants::$ERROR_INVALID_PATH);
        }

        $filesystem = new Filesystem();

        $sourcePath = $uploadFile->getRealPath() ?: $uploadFile->getPathname();
        if ($filesystem->exists($sourcePath)) {

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
            $uploadFile->move($this->storageAdapter->getRootDir($this->storageType) . '/tmp', $tmpFileName);

            $uploadTarget = $this->storageAdapter->deploy('tmp/' . $tmpFileName, $objTarget->path . '/' . $fileName, false, $this->storageType);
            if (!$uploadTarget instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("error upload file");
            }

            $objFinalFile = $this->storageAdapter->get($uploadTarget->path, $this->storageType);
            if (!$objFinalFile instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("error upload file");
            }

            $response->setUuid($objFinalFile->uuid);
            $response->setRootFileName($objFinalFile->path);
            $response->setFileName($objFinalFile->basename);

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
        try {

            if ($mandantInfo->getAccessDownload() === false) {
                throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
            }

            $target = $this->prepareSrcPath($target, $mandantInfo, true);

            $objTarget = $this->storageAdapter->get($target, $this->storageType);
            if (!$objTarget instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("invalid target fileMount");
            }

            if ($objTarget->type === 'folder') {
                throw new AlpdeskCoreFilemanagementException("invalid src file - must be file");
            }

            $pDest = $mandantInfo->getRootDir() . '/' . $objTarget->path;

            $filesystem = new Filesystem();

            if ($filesystem->exists($pDest) && \is_file($pDest)) {

                $response = new BinaryFileResponse($pDest);
                $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, \str_replace('/', '_', $objTarget->basename));

                return $response;

            }

            throw new AlpdeskCoreFilemanagementException("src-File not found on server");

        } catch (\Exception $ex) {
            throw new AlpdeskCoreFilemanagementException("error at downloadFile - " . $ex->getMessage());
        }

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

            $src = $this->prepareSrcPath((string)$finderData['src'], $mandantInfo, true);

            $objTargetSrc = $this->storageAdapter->get($src, $this->storageType);
            if (!$objTargetSrc instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("invalid src fileMount");
            }

            if ($objTargetSrc->type !== 'folder') {
                throw new AlpdeskCoreFilemanagementException("invalid src folder - must be folder");
            }

            $listPath = $this->storageAdapter->getRootDir($this->storageType) . '/' . $objTargetSrc->path;

            $files = $this->storageAdapter->listDir($listPath, $this->storageType);

            foreach ($files as $file) {

                $objFileTmp = $this->storageAdapter->get($objTargetSrc->path . '/' . $file, $this->storageType);

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

            if (!\array_key_exists('target', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
            }

            $target = $this->storageAdapter->sanitizePath((string)$finderData['target']);

            if ($target === "") {
                throw new AlpdeskCoreFilemanagementException("No valid mode in target. Must be 'file' or 'dir'");
            }

            $src = $this->prepareSrcPath((string)$finderData['src'], $mandantInfo, false);

            if ($this->storageAdapter->get($src, $this->storageType)) {
                throw new AlpdeskCoreFilemanagementException("target still exists");
            }

            if ($target === 'file') {
                $objTargetModel = $this->storageAdapter->createFile($src, 'init', $this->storageType);
            } else if ($target === 'dir') {
                $objTargetModel = $this->storageAdapter->createDirectory($src, $this->storageType);
            } else {
                throw new AlpdeskCoreFilemanagementException("invalid targetMode");
            }

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

            $src = $this->prepareSrcPath((string)$finderData['src'], $mandantInfo, true);

            $this->storageAdapter->delete($src, $this->storageType);

            if ($this->storageAdapter->exists($src, $this->storageType)) {
                throw new AlpdeskCoreFilemanagementException("target still exists");
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

            $src = $this->prepareSrcPath((string)$finderData['src'], $mandantInfo, true);

            if (!\array_key_exists('target', $finderData) || $finderData['target'] === '') {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
            }

            $renameObject = $this->storageAdapter->rename($src, (string)$finderData['target'], $this->storageType);
            if (!$renameObject instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("error rename");
            }

            return [
                'uuid' => $renameObject->uuid,
                'path' => $renameObject->path,
                'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $renameObject->path)
            ];

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
        } else if ($accessCheck === true && $mandantInfo->getAccessMove() === false) {
            throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
        }

        try {

            if (!\array_key_exists('src', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
            }

            $src = $this->prepareSrcPath((string)$finderData['src'], $mandantInfo, true);

            if (!\array_key_exists('target', $finderData) || $finderData['target'] === '') {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
            }

            $target = $this->prepareSrcPath((string)$finderData['target'], $mandantInfo, false);

            if ($copy === true) {

                $copyObject = $this->storageAdapter->copy($src, $target, $this->storageType);
                if (!$copyObject instanceof StorageObject) {
                    throw new AlpdeskCoreFilemanagementException("src file by uuid not found");
                }

                return [
                    'uuid' => $copyObject->uuid,
                    'name' => $copyObject->basename,
                    'path' => $copyObject->path,
                    'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $copyObject->path)
                ];

            }

            $moveObject = $this->storageAdapter->move($src, $target, $this->storageType);
            if (!$moveObject instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("src file by uuid not found");
            }

            return [
                'uuid' => $moveObject->uuid,
                'name' => $moveObject->basename,
                'path' => $moveObject->path,
                'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $moveObject->path)
            ];

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

            $src = $this->prepareSrcPath((string)$finderData['src'], $mandantInfo, true);

            $objFileModelSrc = $this->storageAdapter->get($src, $this->storageType);
            if (!$objFileModelSrc instanceof StorageObject) {
                throw new AlpdeskCoreFilemanagementException("src file by uuid not found");
            }

            if ($objFileModelSrc->type !== 'file') {
                throw new AlpdeskCoreFilemanagementException("error - src must be file");
            }

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
                $this->storageAdapter->setMeta($objFileModelSrc, $this->storageType);

            }

            return [
                'uuid' => $objFileModelSrc->uuid,
                'name' => $objFileModelSrc->basename,
                'path' => $objFileModelSrc->path,
                'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $objFileModelSrc->path),
                'extention' => $objFileModelSrc->extension,
                'size' => $objFileModelSrc->size,
                'isimage' => $objFileModelSrc->isImage,
                'public' => $objFileModelSrc->isPublic,
                'url' => $objFileModelSrc->url,
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
     * @throws AlpdeskCoreFilemanagementException
     */
    public function upload(UploadedFile $uploadFile, string $target, AlpdeskcoreUser $user): AlpdeskCoreFileuploadResponse
    {
        try {

            $eventFileManagement = new AlpdeskCoreFileManagementRequestEvent();

            $eventFileManagement->setAction('upload');
            $eventFileManagement->setStorageAdapter('local');
            $eventFileManagement->setRequestData([
                'file' => $uploadFile,
                'target' => $target
            ]);
            $eventFileManagement->setUser($user);

            $this->eventService->getDispatcher()->dispatch($eventFileManagement, AlpdeskCoreFileManagementRequestEvent::NAME);

            $this->storageType = $eventFileManagement->getStorageAdapter();
            $uploadFile = $eventFileManagement->getRequestData()['file'];
            $target = $eventFileManagement->getRequestData()['target'];

            $mandantInfo = $this->getMandantData($user);

            $response = new AlpdeskCoreFileuploadResponse();

            $this->copyToTarget($uploadFile, $target, $mandantInfo, $response);

            $response->setUsername($user->getUsername());
            $response->setAlpdesk_token($user->getUsedToken());
            $response->setMandantInfo($mandantInfo);

            return $response;

        } catch (\Throwable $ex) {
            throw new AlpdeskCoreFilemanagementException($ex->getMessage());
        }

    }

    /**
     * @param AlpdeskcoreUser $user
     * @param array $downloadData
     * @return BinaryFileResponse
     * @throws AlpdeskCoreFilemanagementException
     */
    public function download(AlpdeskcoreUser $user, array $downloadData): BinaryFileResponse
    {
        try {

            if (!\array_key_exists('target', $downloadData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameters for download");
            }

            $eventFileManagement = new AlpdeskCoreFileManagementRequestEvent();

            $eventFileManagement->setAction('download');
            $eventFileManagement->setStorageAdapter('local');
            $eventFileManagement->setRequestData($downloadData);
            $eventFileManagement->setUser($user);

            $this->eventService->getDispatcher()->dispatch($eventFileManagement, AlpdeskCoreFileManagementRequestEvent::NAME);

            $this->storageType = $eventFileManagement->getStorageAdapter();
            $downloadData = $eventFileManagement->getRequestData();

            $target = (string)$downloadData['target'];
            $mandantInfo = $this->getMandantData($user);

            return $this->downloadFile($target, $mandantInfo);

        } catch (\Exception $ex) {
            throw new AlpdeskCoreFilemanagementException("error at download - " . $ex->getMessage());
        }

    }

    /**
     * @param AlpdeskcoreUser $user
     * @param array $finderData
     * @return array|bool
     * @throws AlpdeskCoreFilemanagementException
     */
    public function finder(AlpdeskcoreUser $user, array $finderData): array|bool
    {
        try {

            if (!\array_key_exists('mode', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter mode for finder");
            }

            $mode = (string)$finderData['mode'];

            $eventFileManagement = new AlpdeskCoreFileManagementRequestEvent();

            $eventFileManagement->setAction($mode);
            $eventFileManagement->setStorageAdapter('local');
            $eventFileManagement->setRequestData($finderData);
            $eventFileManagement->setUser($user);

            $this->eventService->getDispatcher()->dispatch($eventFileManagement, AlpdeskCoreFileManagementRequestEvent::NAME);

            $this->storageType = $eventFileManagement->getStorageAdapter();
            $finderData = $eventFileManagement->getRequestData();
            $mode = $eventFileManagement->getAction();

            $mandantInfo = $this->getMandantData($user);

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

        } catch (\Exception $ex) {
            throw new AlpdeskCoreFilemanagementException("error at finder - " . $ex->getMessage());
        }

    }

}
