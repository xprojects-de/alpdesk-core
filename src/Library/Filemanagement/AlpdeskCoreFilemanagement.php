<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Filemanagement;

use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreFilemanagementException;
use Alpdesk\AlpdeskCore\Library\Filemanagement\AlpdeskCoreFileuploadResponse;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Contao\FilesModel;
use Contao\File;
use Contao\Folder;
use Contao\StringUtil;
use Contao\System;
use Contao\Environment;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;

class AlpdeskCoreFilemanagement {

  protected string $rootDir;

  public function __construct(string $rootDir) {
    $this->rootDir = $rootDir;
  }

  private function getMandantInformation(AlpdeskcoreUser $user): AlpdescCoreBaseMandantInfo {
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
        $mInfo->setFilemount_uuid($user->getHomeDir());
        $mInfo->setFilemount_path($rootPathMember->path);
        $mInfo->setFilemount_rootpath($this->rootDir . '/' . $rootPathMember->path);
      }

      $mInfo->setId(intval($mandantInfo->id));
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
    } else {
      throw new AlpdeskCoreFilemanagementException("cannot get Mandantinformations");
    }
  }

  private static function endsWith(string $haystack, string $needle): bool {
    return (\preg_match('#' . $haystack . '$#', $needle) == 1);
  }

  private static function startsWith(string $haystack, string $needle): bool {
    return ($needle[0] == $haystack);
  }

  private static function preparePath(string $src): string {

    if (\str_contains($src, '..')) {
      throw new AlpdeskCoreFilemanagementException("invalid levelup sequence ..");
    }

    if (\str_contains($src, '~')) {
      throw new AlpdeskCoreFilemanagementException("invalid tilde sequence");
    }

    if (self::startsWith('/', $src)) {
      $src = \substr($src, 1, \strlen($src));
    }

    if (self::endsWith('/', $src)) {
      $src = \substr($src, 0, \strlen($src) - 1);
    }

    if ($src === null) {
      throw new AlpdeskCoreFilemanagementException("No valid src file");
    }

    return $src;
  }

  private static function scanDir($strFolder): array {

    $strRootDir = System::getContainer()->getParameter('kernel.project_dir');

    $strFolder = $strRootDir . '/' . $strFolder;

    $arrReturn = [];

    foreach (\scandir($strFolder, SCANDIR_SORT_ASCENDING) as $strFile) {
      if ($strFile == '.' || $strFile == '..') {
        continue;
      }

      $arrReturn[] = $strFile;
    }

    return $arrReturn;
  }

  private function copyToTarget(UploadedFile $uploadFile, string $target, AlpdescCoreBaseMandantInfo $mandantInfo, AlpdeskCoreFileuploadResponse $response): void {

    if ($mandantInfo->getAccessUpload() == false) {
      throw new AlpdeskCoreFilemanagementException("access denied");
    }

    $target = self::preparePath($target);

    if ($target === '/' || $target === '') {
      $target = StringUtil::binToUuid($mandantInfo->getFilemount_uuid());
    }

    $objTarget = FilesModel::findByUuid($target);
    if ($objTarget === null) {
      throw new AlpdeskCoreFilemanagementException("invalid target filemount");
    }

    if (\file_exists($uploadFile->getPathName())) {

      $fileName = $uploadFile->getClientOriginalName();
      $tmpFileName = time() . '_' . $fileName;
      $uploadFile->move($this->rootDir . '/' . $objTarget->path, $tmpFileName);

      $tmpFile = new File($objTarget->path . '/' . $tmpFileName);
      if (!$tmpFile->exists()) {
        throw new AlpdeskCoreFilemanagementException("error upload file");
      }
      $tmpFile->copyTo($objTarget->path . '/' . $fileName);
      $tmpFile->delete();

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

  private function downloadFile(string $target, AlpdescCoreBaseMandantInfo $mandantInfo): BinaryFileResponse {

    if ($mandantInfo->getAccessDownload() == false) {
      throw new AlpdeskCoreFilemanagementException("access denied");
    }

    $target = self::preparePath($target);

    $objTarget = FilesModel::findByUuid($target);
    if ($objTarget === null) {
      throw new AlpdeskCoreFilemanagementException("invalid target filemount");
    }

    if ($objTarget->type === 'folder') {
      throw new AlpdeskCoreFilemanagementException("invalid src file - must be file");
    }

    // @TODO add AccessCheck for Mandant
    $objFileModel = FilesModel::findByUuid($target);
    if ($objFileModel === null) {
      throw new AlpdeskCoreFilemanagementException("src file not found");
    }

    $target = $objFileModel->path;
    $pDest = $mandantInfo->getRootDir() . '/' . $target;

    if (\file_exists($pDest) && \is_file($pDest)) {
      $response = new BinaryFileResponse($pDest);
      $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
      $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, str_replace('/', '_', $target));
      return $response;
    } else {
      throw new AlpdeskCoreFilemanagementException("src-File not found on server");
    }
  }

  public static function listFolder(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo): array {

    try {

      if (!\array_key_exists('src', $finderData)) {
        throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
      }

      $src = self::preparePath(((string) $finderData['src']));

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

          array_push($data, array(
              'name' => $basename,
              'uuid' => StringUtil::binToUuid($objFileTmp->uuid),
              'extention' => $objFileTmp->extension,
              'public' => $public,
              'url' => $url,
              'isFolder' => ($objFileTmp->type === 'folder'),
              'size' => $size,
              'isimage' => $isImage
          ));
        }
      }

      return $data;
    } catch (\Exception $ex) {
      throw new AlpdeskCoreFilemanagementException("error at listFolder - " . $ex->getMessage());
    }
  }

  public static function create(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): string {

    if ($accessCheck == true && $mandantInfo->getAccessCreate() == false) {
      throw new AlpdeskCoreFilemanagementException("access denied");
    }

    try {

      if (!\array_key_exists('src', $finderData)) {
        throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
      }

      $src = self::preparePath(((string) $finderData['src']));

      if (!\array_key_exists('target', $finderData)) {
        throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
      }

      $target = self::preparePath(((string) $finderData['target']));

      if ($target == null || $target == "") {
        throw new AlpdeskCoreFilemanagementException("No valid mode in target. Must be 'file' or 'dir'");
      }

      $objTargetBase = FilesModel::findByUuid(StringUtil::binToUuid($mandantInfo->getFilemount_uuid()));
      if ($objTargetBase === null) {
        throw new AlpdeskCoreFilemanagementException("invalid Mandant Filemount");
      }

      if ($target === 'file') {
        $cFile = new File($objTargetBase->path . '/' . $src);
        $cFile->write('init');
        $cFile->close();
      } else if ($target === 'dir') {
        $cFolder = new Folder($objTargetBase->path . '/' . $src);
      } else {
        throw new AlpdeskCoreFilemanagementException("invalid targetmode");
      }

      $objTargetModel = FilesModel::findByPath($objTargetBase->path . '/' . $src);
      if ($objTargetBase === null) {
        throw new AlpdeskCoreFilemanagementException("invalid Mandant Filemount");
      }

      return StringUtil::binToUuid($objTargetModel->uuid);
    } catch (\Exception $ex) {
      throw new AlpdeskCoreFilemanagementException("error at create - " . $ex->getMessage());
    }
  }

  public static function delete(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): void {

    if ($accessCheck == true && $mandantInfo->getAccessDelete() == false) {
      throw new AlpdeskCoreFilemanagementException("access denied");
    }

    try {

      if (!\array_key_exists('src', $finderData)) {
        throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
      }

      $src = self::preparePath(((string) $finderData['src']));

      // @TODO add AccessCheck for Mandant
      $objFileModelSrc = FilesModel::findByUuid($src);
      if ($objFileModelSrc === null) {
        throw new AlpdeskCoreFilemanagementException("src not found");
      }

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

  public static function rename(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): string {

    if ($accessCheck == true && $mandantInfo->getAccessRename() == false) {
      throw new AlpdeskCoreFilemanagementException("access denied");
    }

    try {

      if (!\array_key_exists('src', $finderData)) {
        throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
      }

      $src = self::preparePath(((string) $finderData['src']));

      if ($src === '') {
        throw new AlpdeskCoreFilemanagementException("invalid src");
      }

      if (!\array_key_exists('target', $finderData)) {
        throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
      }

      $target = self::preparePath(((string) $finderData['target']));

      if ($target === '') {
        throw new AlpdeskCoreFilemanagementException("invalid target");
      }

      // @TODO add AccessCheck for Mandant
      $objFileModelSrc = FilesModel::findByUuid($src);
      if ($objFileModelSrc === null) {
        throw new AlpdeskCoreFilemanagementException("src-File by uuid not found on server");
      }

      if ($objFileModelSrc->type === 'folder') {

        $srcObject = new Folder($objFileModelSrc->path);
        $parent = \substr($srcObject->path, 0, (\strlen($srcObject->path) - \strlen($srcObject->basename)));
        $srcObject->renameTo($parent . $target);

        $targetObject = FilesModel::findByPath($parent . $target);
        if ($targetObject === null) {
          throw new AlpdeskCoreFilemanagementException("error rename");
        }

        return StringUtil::binToUuid($targetObject->uuid);
      } else if ($objFileModelSrc->type === 'file') {

        $srcObject = new File($objFileModelSrc->path);
        $parent = \substr($srcObject->path, 0, (\strlen($srcObject->path) - \strlen($srcObject->basename)));
        $srcObject->renameTo($parent . $target);

        $targetObject = FilesModel::findByPath($parent . $target);
        if ($targetObject === null) {
          throw new AlpdeskCoreFilemanagementException("error rename");
        }

        return StringUtil::binToUuid($targetObject->uuid);
      } else {
        throw new AlpdeskCoreFilemanagementException("error at copy - invalid source");
      }
    } catch (\Exception $ex) {
      throw new AlpdeskCoreFilemanagementException("error at rename - " . $ex->getMessage());
    }
  }

  public static function moveOrCopy(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo, bool $copy, bool $accessCheck = true): string {

    if ($copy == true) {

      if ($accessCheck == true && $mandantInfo->getAccessCopy() == false) {
        throw new AlpdeskCoreFilemanagementException("access denied");
      }
    } else {
      if ($accessCheck == true && $mandantInfo->getAccessMove() == false) {
        throw new AlpdeskCoreFilemanagementException("access denied");
      }
    }

    try {

      if (!\array_key_exists('src', $finderData)) {
        throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
      }

      $src = self::preparePath(((string) $finderData['src']));

      if ($src === '') {
        throw new AlpdeskCoreFilemanagementException("invalid src");
      }

      if (!\array_key_exists('target', $finderData)) {
        throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
      }

      $target = self::preparePath(((string) $finderData['target']));

      if ($target === '/' || $target === '') {
        $target = StringUtil::binToUuid($mandantInfo->getFilemount_uuid());
      }

      // @TODO add AccessCheck for Mandant
      $objFileModelSrc = FilesModel::findByUuid(self::preparePath($src));
      if ($objFileModelSrc === null) {
        throw new AlpdeskCoreFilemanagementException("src file by uuid not found");
      }

      $objFileModelTarget = FilesModel::findByUuid(self::preparePath($target));
      if ($objFileModelTarget === null) {
        throw new AlpdeskCoreFilemanagementException("src file by uuid not found");
      }

      if ($objFileModelTarget->type !== 'folder') {
        throw new AlpdeskCoreFilemanagementException("error - target must be folder");
      }

      if ($objFileModelSrc->type === 'folder') {

        $srcObject = new Folder($objFileModelSrc->path);
        $srcObject->copyTo($objFileModelTarget->path . '/' . $srcObject->basename);

        if (!$copy) {
          $srcObject->delete();
        }

        $targetObject = FilesModel::findByPath($objFileModelTarget->path . '/' . $srcObject->basename);
        if ($targetObject === null) {
          throw new AlpdeskCoreFilemanagementException("error rename");
        }

        return StringUtil::binToUuid($targetObject->uuid);
      } else if ($objFileModelSrc->type === 'file') {

        $srcObject = new File($objFileModelSrc->path);
        $srcObject->copyTo($objFileModelTarget->path . '/' . $srcObject->basename);

        if (!$copy) {
          $srcObject->delete();
        }

        $targetObject = FilesModel::findByPath($objFileModelTarget->path . '/' . $srcObject->basename);
        if ($targetObject === null) {
          throw new AlpdeskCoreFilemanagementException("error rename");
        }

        return StringUtil::binToUuid($targetObject->uuid);
      } else {
        throw new AlpdeskCoreFilemanagementException("error at copy - invalid source");
      }
    } catch (\Exception $ex) {
      throw new AlpdeskCoreFilemanagementException("error at moveOrCopy - " . $ex->getMessage());
    }
  }

  public static function meta(array $finderData, AlpdescCoreBaseMandantInfo $mandantInfo): array {

    try {

      if (!\array_key_exists('src', $finderData)) {
        throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
      }

      $src = self::preparePath(((string) $finderData['src']));

      if ($src === '') {
        throw new AlpdeskCoreFilemanagementException("invalid src");
      }

      // @TODO add AccessCheck for Mandant
      $objFileModelSrc = FilesModel::findByUuid($src);
      if ($objFileModelSrc === null) {
        throw new AlpdeskCoreFilemanagementException("src file by uuid not found");
      }

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

        $metaSet = ((array) $finderData['meta']);

        foreach ($metaSet as $key => $value) {
          if ($key !== '' && \is_array($value)) {
            foreach ($value as $valueKey => $valueValue) {
              if ($valueKey === 'title' || $valueKey === 'alt' || $valueKey === 'link' || $valueKey === 'caption') {
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
          'extention' => $extention,
          'size' => $size,
          'isimage' => $isImage,
          'public' => $public,
          'url' => $url,
          'meta' => $metaData
      ];
    } catch (\Exception $ex) {
      throw new AlpdeskCoreFilemanagementException("error at moveOrCopy - " . $ex->getMessage());
    }
  }

  public function upload(UploadedFile $uploadFile, string $target, AlpdeskcoreUser $user): AlpdeskCoreFileuploadResponse {
    if ($uploadFile == null) {
      throw new AlpdeskCoreFilemanagementException("invalid key-parameters for upload");
    }
    $mandantInfo = $this->getMandantInformation($user);
    $response = new AlpdeskCoreFileuploadResponse();
    $this->copyToTarget($uploadFile, $target, $mandantInfo, $response);
    $response->setUsername($user->getUsername());
    $response->setAlpdesk_token($user->getUsedToken());
    $response->setMandantInfo($mandantInfo);
    return $response;
  }

  public function download(AlpdeskcoreUser $user, array $downloadData): BinaryFileResponse {
    if (!\array_key_exists('target', $downloadData)) {
      throw new AlpdeskCoreFilemanagementException("invalid key-parameters for download");
    }
    $target = (string) $downloadData['target'];
    $mandantInfo = $this->getMandantInformation($user);
    return $this->downloadFile($target, $mandantInfo);
  }

  public function finder(AlpdeskcoreUser $user, array $finderData) {

    if (!\array_key_exists('mode', $finderData)) {
      throw new AlpdeskCoreFilemanagementException("invalid key-parameter mode for finder");
    }

    $mode = (string) $finderData['mode'];

    $mandantInfo = $this->getMandantInformation($user);

    switch ($mode) {
      case 'list': {
          return self::listFolder($finderData, $mandantInfo);
        }
      case 'create': {
          return self::create($finderData, $mandantInfo);
        }
      case 'delete': {
          self::delete($finderData, $mandantInfo);
          return true;
        }
      case 'rename': {
          return self::rename($finderData, $mandantInfo);
        }
      case 'move': {
          return self::moveOrcopy($finderData, $mandantInfo, false);
        }
      case 'copy': {
          return self::moveOrcopy($finderData, $mandantInfo, true);
        }
      case 'meta': {
          return self::meta($finderData, $mandantInfo);
        }
      default:
        throw new AlpdeskCoreFilemanagementException("invalid mode for finder");
    }
  }

}
