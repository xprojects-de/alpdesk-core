<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Filemanagement;

use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreFilemanagementFinderDeleteEvent;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreFilemanagementFinderListEvent;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreFilemanagementFinderMetaEvent;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreFilemanagementException;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Contao\FilesModel;
use Contao\File;
use Contao\Folder;
use Contao\StringUtil;
use Contao\System;
use Contao\Environment;
use Contao\Config;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;

class AlpdeskCoreFilemanagement
{
    protected string $rootDir;
    private AlpdeskCoreEventService $eventService;

    public function __construct(string $rootDir, AlpdeskCoreEventService $eventService)
    {
        $this->rootDir = $rootDir;
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

            $rootPath = FilesModel::findByUuid($mandantInfo->filemount);

            $mInfo->setRootDir($this->rootDir);

            $mInfo->setFilemountmandant_uuid($mandantInfo->filemount);
            $mInfo->setFilemountmandant_path($rootPath->path);
            $mInfo->setFilemountmandant_rootpath($this->rootDir . '/' . $rootPath->path);

            $mInfo->setFilemount_uuid($mandantInfo->filemount);
            $mInfo->setFilemount_path($rootPath->path);
            $mInfo->setFilemount_rootpath($this->rootDir . '/' . $rootPath->path);

            if ($user->getHomeDir() !== null) {

                $rootPathMember = FilesModel::findByUuid($user->getHomeDir());
                if ($rootPathMember !== null) {
                    $mInfo->setFilemount_uuid($rootPathMember->uuid);
                    $mInfo->setFilemount_path($rootPathMember->path);
                    $mInfo->setFilemount_rootpath($this->rootDir . '/' . $rootPathMember->path);
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

    private static function endsWith(string $haystack, string $needle): bool
    {
        return (\preg_match('#' . $haystack . '$#', $needle) === 1);
    }

    private static function startsWith($startString, $string): bool
    {
        $len = \strlen($startString);
        $sub = \substr($string, 0, $len);

        return ($sub === $startString);
    }

    /**
     * @param string $src
     * @return string
     * @throws AlpdeskCoreFilemanagementException
     */
    private static function preparePath(string $src): string
    {
        // Remove invisible control characters and unused code points
        $src = \preg_replace('/[\pC]/u', '', $src);

        // Remove special characters not supported on e.g. Windows
        $src = \str_replace(array('\\', ':', '*', '?', '"', '<', '>', '|'), '-', $src);

        if (\str_contains($src, '..')) {
            throw new AlpdeskCoreFilemanagementException("invalid levelup sequence ..", AlpdeskCoreConstants::$ERROR_INVALID_INPUT);
        }

        if (\str_contains($src, '~')) {
            throw new AlpdeskCoreFilemanagementException("invalid tilde sequence", AlpdeskCoreConstants::$ERROR_INVALID_INPUT);
        }

        if (self::startsWith('/', $src)) {
            $src = \substr($src, 1, \strlen($src));
        }

        if (self::endsWith('/', $src)) {
            $src = \substr($src, 0, \strlen($src) - 1);
        }

        if ($src === null) {
            throw new AlpdeskCoreFilemanagementException("No valid src file", AlpdeskCoreConstants::$ERROR_INVALID_INPUT);
        }

        return $src;
    }

    private static function scanDir($strFolder): array
    {
        $strRootDir = System::getContainer()->getParameter('kernel.project_dir');

        $strFolder = $strRootDir . '/' . $strFolder;

        $arrReturn = [];

        foreach (\scandir($strFolder, SCANDIR_SORT_ASCENDING) as $strFile) {
            if ($strFile === '.' || $strFile === '..') {
                continue;
            }

            $arrReturn[] = $strFile;
        }

        return $arrReturn;
    }

    /**
     * @param $basePath
     * @param $srcPath
     * @param AlpdescCoreBaseMandantInfo $mandantInfo
     * @throws AlpdeskCoreFilemanagementException
     */
    private static function checkFilemountPermission($basePath, $srcPath, AlpdescCoreBaseMandantInfo $mandantInfo): void
    {
        if ($basePath === null) {

            $baseObject = FilesModel::findByUuid(StringUtil::binToUuid($mandantInfo->getFilemount_uuid()));
            if ($baseObject === null) {
                throw new AlpdeskCoreFilemanagementException("invalid mandant filemount", AlpdeskCoreConstants::$ERROR_INVALID_PATH);
            }

            $basePath = $baseObject->path;
        }

        if (!self::startsWith($basePath, $srcPath)) {
            throw new AlpdeskCoreFilemanagementException("invalid mandant filemount - access denied", AlpdeskCoreConstants::$ERROR_INVALID_PATH);
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

        $target = self::preparePath($target);

        if ($target === '/' || $target === '') {
            $target = StringUtil::binToUuid($mandantInfo->getFilemount_uuid());
        }

        $objTarget = FilesModel::findByUuid($target);
        if ($objTarget === null) {
            throw new AlpdeskCoreFilemanagementException("invalid target filemount", AlpdeskCoreConstants::$ERROR_INVALID_PATH);
        }

        self::checkFilemountPermission(null, $objTarget->path, $mandantInfo);

        if (\file_exists($uploadFile->getPathName())) {

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

            $tmpFileName = time() . '_' . $fileName;
            $uploadFile->move($this->rootDir . '/' . $objTarget->path, $tmpFileName);

            $tmpFile = new File($objTarget->path . '/' . $tmpFileName);
            if (!$tmpFile->exists()) {
                throw new AlpdeskCoreFilemanagementException("error upload file");
            }

            if (\file_exists($mandantInfo->getRootDir() . '/' . $objTarget->path . '/' . $fileName)) {
                $fileName = time() . '_' . $fileName;
            }

            $tmpFile->renameTo($objTarget->path . '/' . $fileName);

            $nFile = new File($objTarget->path . '/' . $fileName);
            if (!$nFile->exists()) {
                throw new AlpdeskCoreFilemanagementException("error upload file");
            }

            $objnFile = FilesModel::findByPath($objTarget->path . '/' . $fileName);
            if ($objnFile === null) {
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

        $target = self::preparePath($target);

        $objTarget = FilesModel::findByUuid($target);
        if ($objTarget === null) {
            throw new AlpdeskCoreFilemanagementException("invalid target filemount");
        }

        self::checkFilemountPermission(null, $objTarget->path, $mandantInfo);

        if ($objTarget->type === 'folder') {
            throw new AlpdeskCoreFilemanagementException("invalid src file - must be file");
        }

        $target = $objTarget->path;
        $pDest = $mandantInfo->getRootDir() . '/' . $target;

        if (\file_exists($pDest) && \is_file($pDest)) {

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
    public static function listFolder(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo): array
    {
        try {

            if (!\array_key_exists('src', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
            }

            $src = self::preparePath(((string)$finderData['src']));

            $data = [];

            $objTargetBase = FilesModel::findByUuid(StringUtil::binToUuid($mandantInfo->getFilemount_uuid()));
            if ($objTargetBase === null) {
                throw new AlpdeskCoreFilemanagementException("invalid Mandant filemount");
            }

            $path = $objTargetBase->path;

            if ($src !== '' && $src !== '/') {

                $objTargetSrc = FilesModel::findByUuid($src);
                if ($objTargetSrc === null) {
                    throw new AlpdeskCoreFilemanagementException("invalid src filemount");
                }

                self::checkFilemountPermission($objTargetBase->path, $objTargetSrc->path, $mandantInfo);

                $path = $objTargetSrc->path;

                if ($objTargetSrc->type !== 'folder') {
                    throw new AlpdeskCoreFilemanagementException("invalid src folder - must be folder");
                }
            }

            $files = self::scanDir($path);

            foreach ($files as $file) {

                $objFileTmp = FilesModel::findByPath($path . '/' . $file);

                if ($objFileTmp !== null) {

                    $public = false;
                    $basename = $objFileTmp->path;
                    $url = '';
                    $size = '';
                    $isImage = false;

                    if ($objFileTmp->type === 'folder') {
                        $tmpFolder = new Folder($objFileTmp->path);
                        $basename = $tmpFolder->basename;
                        $public = $tmpFolder->isUnprotected();
                    } else if ($objFileTmp->type === 'file') {
                        $tmpFile = new File($objFileTmp->path);
                        $basename = $tmpFile->basename;
                        $public = $tmpFile->isUnprotected();
                        if ($public === true) {
                            $url = Environment::get('base') . $tmpFile->path;
                        }
                        $size = $tmpFile->size;
                        $isImage = ($tmpFile->isCmykImage || $tmpFile->isGdImage || $tmpFile->isImage || $tmpFile->isRgbImage || $tmpFile->isSvgImage);
                    }

                    $data[] = [
                        'name' => $basename,
                        'path' => $objFileTmp->path,
                        'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $objFileTmp->path),
                        'uuid' => StringUtil::binToUuid($objFileTmp->uuid),
                        'extention' => $objFileTmp->extension,
                        'public' => $public,
                        'url' => $url,
                        'isFolder' => ($objFileTmp->type === 'folder'),
                        'size' => $size,
                        'isimage' => $isImage
                    ];
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
    public static function create(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): array
    {
        if ($accessCheck === true && $mandantInfo->getAccessCreate() === false) {
            throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
        }

        try {

            if (!\array_key_exists('src', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
            }

            $src = self::preparePath(((string)$finderData['src']));

            if (!\array_key_exists('target', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
            }

            $target = self::preparePath(((string)$finderData['target']));

            if ($target === "") {
                throw new AlpdeskCoreFilemanagementException("No valid mode in target. Must be 'file' or 'dir'");
            }

            $objTargetBase = FilesModel::findByUuid(StringUtil::binToUuid($mandantInfo->getFilemount_uuid()));
            if ($objTargetBase === null) {
                throw new AlpdeskCoreFilemanagementException("invalid Mandant Filemount");
            }

            // No Check neccessarry
            // self::checkFilemountPermission($objTarget->path, $objTargetBase->path . '/' . $src, $mandantInfo);

            if (\file_exists($mandantInfo->getRootDir() . '/' . $objTargetBase->path . '/' . $src)) {
                throw new AlpdeskCoreFilemanagementException("target still exists");
            }

            if ($target === 'file') {

                $cFile = new File($objTargetBase->path . '/' . $src);
                $cFile->write('init');
                $cFile->close();

            } else if ($target === 'dir') {

                new Folder($objTargetBase->path . '/' . $src);

            } else {
                throw new AlpdeskCoreFilemanagementException("invalid targetmode");
            }

            $objTargetModel = FilesModel::findByPath($objTargetBase->path . '/' . $src);
            if ($objTargetModel === null) {
                throw new AlpdeskCoreFilemanagementException("invalid Mandant Filemount");
            }

            return [
                'uuid' => StringUtil::binToUuid($objTargetModel->uuid),
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
    public static function delete(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): void
    {
        if ($accessCheck === true && $mandantInfo->getAccessDelete() === false) {
            throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
        }

        try {

            if (!\array_key_exists('src', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
            }

            $src = self::preparePath(((string)$finderData['src']));

            $objFileModelSrc = FilesModel::findByUuid($src);
            if ($objFileModelSrc === null) {
                throw new AlpdeskCoreFilemanagementException("src not found");
            }

            self::checkFilemountPermission(null, $objFileModelSrc->path, $mandantInfo);

            if ($objFileModelSrc->type === 'folder') {
                $srcObject = new Folder($objFileModelSrc->path);
                $srcObject->delete();
            } else if ($objFileModelSrc->type === 'file') {
                $srcObject = new File($objFileModelSrc->path);
                $srcObject->delete();
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
    public static function rename(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): array
    {
        if ($accessCheck === true && $mandantInfo->getAccessRename() === false) {
            throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
        }

        try {

            if (!\array_key_exists('src', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
            }

            $src = self::preparePath(((string)$finderData['src']));

            if ($src === '') {
                throw new AlpdeskCoreFilemanagementException("invalid src");
            }

            if (!\array_key_exists('target', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
            }

            $target = self::preparePath(((string)$finderData['target']));

            if ($target === '') {
                throw new AlpdeskCoreFilemanagementException("invalid target");
            }

            $objFileModelSrc = FilesModel::findByUuid($src);
            if ($objFileModelSrc === null) {
                throw new AlpdeskCoreFilemanagementException("src-File by uuid not found on server");
            }

            self::checkFilemountPermission(null, $objFileModelSrc->path, $mandantInfo);

            if ($objFileModelSrc->type === 'folder') {

                $srcObject = new Folder($objFileModelSrc->path);
                $parent = \substr($srcObject->path, 0, (\strlen($srcObject->path) - \strlen($srcObject->basename)));

                if (\file_exists($mandantInfo->getRootDir() . '/' . $parent . $target)) {
                    throw new AlpdeskCoreFilemanagementException("target still exists");
                }

                $srcObject->renameTo($parent . $target);

                $targetObject = FilesModel::findByPath($parent . $target);
                if ($targetObject === null) {
                    throw new AlpdeskCoreFilemanagementException("error rename");
                }

                return [
                    'uuid' => StringUtil::binToUuid($targetObject->uuid),
                    'path' => $targetObject->path,
                    'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $targetObject->path)
                ];
            } else if ($objFileModelSrc->type === 'file') {

                $srcObject = new File($objFileModelSrc->path);
                $parent = \substr($srcObject->path, 0, (\strlen($srcObject->path) - \strlen($srcObject->basename)));

                if (\file_exists($mandantInfo->getRootDir() . '/' . $parent . $target)) {
                    throw new AlpdeskCoreFilemanagementException("target still exists");
                }

                $srcObject->renameTo($parent . $target);

                $targetObject = FilesModel::findByPath($parent . $target);
                if ($targetObject === null) {
                    throw new AlpdeskCoreFilemanagementException("error rename");
                }

                return [
                    'uuid' => StringUtil::binToUuid($targetObject->uuid),
                    'path' => $targetObject->path,
                    'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $targetObject->path)
                ];
            } else {
                throw new AlpdeskCoreFilemanagementException("error at copy - invalid source");
            }
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
    public static function moveOrCopy(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $copy, bool $accessCheck = true): array
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

            $src = self::preparePath(((string)$finderData['src']));

            if ($src === '') {
                throw new AlpdeskCoreFilemanagementException("invalid src");
            }

            if (!\array_key_exists('target', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
            }

            $target = self::preparePath(((string)$finderData['target']));

            if ($target === '/' || $target === '') {
                $target = StringUtil::binToUuid($mandantInfo->getFilemount_uuid());
            }

            $objFileModelSrc = FilesModel::findByUuid($src);
            if ($objFileModelSrc === null) {
                throw new AlpdeskCoreFilemanagementException("src file by uuid not found");
            }

            self::checkFilemountPermission(null, $objFileModelSrc->path, $mandantInfo);

            $objFileModelTarget = FilesModel::findByUuid($target);
            if ($objFileModelTarget === null) {
                throw new AlpdeskCoreFilemanagementException("src file by uuid not found");
            }

            self::checkFilemountPermission(null, $objFileModelTarget->path, $mandantInfo);

            if ($objFileModelTarget->type !== 'folder') {
                throw new AlpdeskCoreFilemanagementException("error - target must be folder");
            }

            if ($objFileModelSrc->type === 'folder') {

                $srcObject = new Folder($objFileModelSrc->path);

                $basename = $srcObject->basename;
                if (\file_exists($mandantInfo->getRootDir() . '/' . $objFileModelTarget->path . '/' . $basename)) {
                    $basename = time() . '_' . $basename;
                }

                if ($copy) {
                    $srcObject->copyTo($objFileModelTarget->path . '/' . $basename);
                } else {
                    $srcObject->renameTo($objFileModelTarget->path . '/' . $basename);
                }

                $targetObject = FilesModel::findByPath($objFileModelTarget->path . '/' . $basename);
                if ($targetObject === null) {
                    throw new AlpdeskCoreFilemanagementException("error rename");
                }

                return [
                    'uuid' => StringUtil::binToUuid($targetObject->uuid),
                    'name' => $basename,
                    'path' => $targetObject->path,
                    'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $targetObject->path)
                ];

            } else if ($objFileModelSrc->type === 'file') {

                $srcObject = new File($objFileModelSrc->path);

                $basename = $srcObject->basename;
                if (\file_exists($mandantInfo->getRootDir() . '/' . $objFileModelTarget->path . '/' . $basename)) {
                    $basename = time() . '_' . $basename;
                }

                if ($copy) {
                    $srcObject->copyTo($objFileModelTarget->path . '/' . $basename);
                } else {
                    $srcObject->renameTo($objFileModelTarget->path . '/' . $basename);
                }

                $targetObject = FilesModel::findByPath($objFileModelTarget->path . '/' . $basename);
                if ($targetObject === null) {
                    throw new AlpdeskCoreFilemanagementException("error rename");
                }

                return [
                    'uuid' => StringUtil::binToUuid($targetObject->uuid),
                    'name' => $basename,
                    'path' => $targetObject->path,
                    'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $targetObject->path)
                ];

            } else {
                throw new AlpdeskCoreFilemanagementException("error at copy - invalid source");
            }

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
    public static function meta(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): array
    {
        try {

            if (!\array_key_exists('src', $finderData)) {
                throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
            }

            $src = self::preparePath(((string)$finderData['src']));

            if ($src === '') {
                throw new AlpdeskCoreFilemanagementException("invalid src");
            }

            $objFileModelSrc = FilesModel::findByUuid($src);
            if ($objFileModelSrc === null) {
                throw new AlpdeskCoreFilemanagementException("src file by uuid not found");
            }

            self::checkFilemountPermission(null, $objFileModelSrc->path, $mandantInfo);

            if ($objFileModelSrc->type !== 'file') {
                throw new AlpdeskCoreFilemanagementException("error - src must be file");
            }

            $srcObject = new File($objFileModelSrc->path);

            if (!$srcObject->exists()) {
                throw new AlpdeskCoreFilemanagementException("error - src file does not exists");
            }

            $url = '';
            $public = $srcObject->isUnprotected();
            if ($public === true) {
                $url = Environment::get('base') . $srcObject->path;
            }
            $basename = $srcObject->basename;
            $extention = $srcObject->extension;
            $size = $srcObject->size;
            $isImage = ($srcObject->isCmykImage || $srcObject->isGdImage || $srcObject->isImage || $srcObject->isRgbImage || $srcObject->isSvgImage);

            $metaData = [];

            if ($objFileModelSrc->meta !== null) {
                $metaData = StringUtil::deserialize($objFileModelSrc->meta);
            }

            if (\array_key_exists('meta', $finderData)) {

                if ($accessCheck === true && $mandantInfo->getAccessCreate() === false) {
                    throw new AlpdeskCoreFilemanagementException("access denied", AlpdeskCoreConstants::$ERROR_ACCESS_DENIED);
                }

                $metaSet = ((array)$finderData['meta']);

                foreach ($metaSet as $key => $value) {

                    if ($key !== '' && \is_array($value)) {

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

                $objFileModelSrc->meta = serialize($metaData);
                $objFileModelSrc->save();

                $metaData = StringUtil::deserialize($objFileModelSrc->meta);
            }

            return [
                'uuid' => StringUtil::binToUuid($objFileModelSrc->uuid),
                'name' => $basename,
                'path' => $objFileModelSrc->path,
                'relativePath' => \str_replace($mandantInfo->getFilemount_path(), '', $objFileModelSrc->path),
                'extention' => $extention,
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
    public function finder(AlpdeskcoreUser $user, array $finderData)
    {
        if (!\array_key_exists('mode', $finderData)) {
            throw new AlpdeskCoreFilemanagementException("invalid key-parameter mode for finder");
        }

        $mode = (string)$finderData['mode'];

        $mandantInfo = $this->getMandantInformation($user);

        switch ($mode) {
            case 'list':
            {
                $finderResponseData = self::listFolder($finderData, $mandantInfo);

                $event = new AlpdeskCoreFilemanagementFinderListEvent($finderData, $finderResponseData, $mandantInfo);
                $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreFilemanagementFinderListEvent::NAME);

                return $event->getResultData();

            }
            case 'create':
            {
                return self::create($finderData, $mandantInfo);
            }
            case 'delete':
            {
                self::delete($finderData, $mandantInfo);

                $event = new AlpdeskCoreFilemanagementFinderDeleteEvent($finderData, true, $mandantInfo);
                $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreFilemanagementFinderDeleteEvent::NAME);

                return $event->getResultData();

            }
            case 'rename':
            {
                return self::rename($finderData, $mandantInfo);
            }
            case 'move':
            {
                return self::moveOrcopy($finderData, $mandantInfo, false);
            }
            case 'copy':
            {
                return self::moveOrcopy($finderData, $mandantInfo, true);
            }
            case 'meta':
            {
                $finderResponseData = self::meta($finderData, $mandantInfo);

                $event = new AlpdeskCoreFilemanagementFinderMetaEvent($finderData, $finderResponseData, $mandantInfo);
                $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreFilemanagementFinderMetaEvent::NAME);

                return $event->getResultData();

            }
            default:
                throw new AlpdeskCoreFilemanagementException("invalid mode for finder", AlpdeskCoreConstants::$ERROR_INVALID_INPUT);
        }
    }
}
