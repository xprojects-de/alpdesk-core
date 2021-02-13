<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Filemanagement;

use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreFilemanagementException;
use Alpdesk\AlpdeskCore\Library\Filemanagement\AlpdeskCoreFileuploadResponse;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Contao\FilesModel;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class AlpdeskCoreFilemanagement {

  protected string $rootDir;

  public function __construct(string $rootDir) {
    $this->rootDir = $rootDir;
  }

  private function getMandantInformation(AlpdeskcoreUser $user): AlpdescCoreBaseMandantInfo {
    $mandantInfo = AlpdeskcoreMandantModel::findById($user->getMandantPid());
    if ($mandantInfo !== null) {
      $rootPath = FilesModel::findByUuid($mandantInfo->filemount);
      $filemount = $mandantInfo->filemount;
      if ($user->getHomeDir() !== null) {
        $rootPath = FilesModel::findByUuid($user->getHomeDir());
        $filemount = $user->getHomeDir();
      }
      $mInfo = new AlpdescCoreBaseMandantInfo();
      $mInfo->setId(intval($mandantInfo->id));
      $mInfo->setMemberId($user->getMemberId());
      $mInfo->setMandant($mandantInfo->mandant);
      $mInfo->setFilemountmandant_uuid($mandantInfo->filemount);
      $mInfo->setFilemount_uuid($filemount);
      $mInfo->setFilemount_path($rootPath->path);
      $mInfo->setFilemount_rootpath($this->rootDir . '/' . $rootPath->path);
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
      throw new AlpdeskCoreFilemanagementException("cannot get Mandant informations");
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

  private function copyToTarget(UploadedFile $uploadFile, string $target, AlpdescCoreBaseMandantInfo $mandantInfo, AlpdeskCoreFileuploadResponse $response): void {

    if ($mandantInfo->getAccessUpload() == false) {
      throw new AlpdeskCoreFilemanagementException("access denied");
    }

    $target = self::preparePath($target);
    $pDest = $mandantInfo->getFilemount_rootpath() . '/' . $target;

    if (\file_exists($uploadFile->getPathName())) {
      $fileName = $uploadFile->getClientOriginalName();
      if (\file_exists($pDest . '/' . $fileName)) {
        $fileName = time() . '_' . $uploadFile->getClientOriginalName();
      }
      $uploadFile->move($pDest, $fileName);
      $response->setRootFileName($pDest . '/' . $fileName);
      $response->setFileName($target . '/' . $fileName);
    } else {
      throw new AlpdeskCoreFilemanagementException("Src-File not found on server");
    }
  }

  private function downloadFile(string $target, AlpdescCoreBaseMandantInfo $mandantInfo): BinaryFileResponse {

    if ($mandantInfo->getAccessDownload() == false) {
      throw new AlpdeskCoreFilemanagementException("access denied");
    }

    $target = self::preparePath($target);
    $pDest = $mandantInfo->getFilemount_rootpath() . '/' . $target;

    if (\file_exists($pDest) && \is_file($pDest)) {
      $response = new BinaryFileResponse($pDest);
      $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
      $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, str_replace('/', '_', $target));
      return $response;
    } else {
      throw new AlpdeskCoreFilemanagementException("src-File not found on server");
    }
  }

  public static function getContentOfDir(string $src, AlpdescCoreBaseMandantInfo $mandantInfo): array {
    try {

      $data = [];

      $pDest = $mandantInfo->getFilemount_rootpath() . '/' . self::preparePath($src);

      $finder = new Finder();
      $finder->depth('== 0')->in($pDest)->sortByType();

      if ($finder->hasResults()) {
        foreach ($finder as $object) {
          $parent = $object->getRelativePath();
          array_push($data, array(
              'name' => $object->getFilename(),
              'isFolder' => ($object->isFile() == false)
          ));
        }
      }

      return $data;
    } catch (\Exception $ex) {
      throw new AlpdeskCoreFilemanagementException("error at getContentOfDir - " . $ex->getMessage());
    }
  }

  public static function create(string $src, string $target, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): void {

    if ($accessCheck == true && $mandantInfo->getAccessCreate() == false) {
      throw new AlpdeskCoreFilemanagementException("access denied");
    }

    try {

      if ($target == null || $target == "") {
        throw new AlpdeskCoreFilemanagementException("No valid mode in target. Must be 'file' or 'dir'");
      }

      $pDest = $mandantInfo->getFilemount_rootpath() . '/' . self::preparePath($src);

      $filesystem = new Filesystem();
      if (!$filesystem->exists($pDest)) {
        if ($target === 'file') {
          $filesystem->touch($pDest);
        } else if ($target === 'dir') {
          $filesystem->mkdir($pDest);
        } else {
          throw new AlpdeskCoreFilemanagementException("invalid targetmode");
        }
      } else {
        throw new AlpdeskCoreFilemanagementException("src still exits");
      }
    } catch (\Exception $ex) {
      throw new AlpdeskCoreFilemanagementException("error at create - " . $ex->getMessage());
    }
  }

  public static function delete(string $src, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): void {

    if ($accessCheck == true && $mandantInfo->getAccessDelete() == false) {
      throw new AlpdeskCoreFilemanagementException("access denied");
    }

    try {

      $pDest = $mandantInfo->getFilemount_rootpath() . '/' . self::preparePath($src);

      $filesystem = new Filesystem();

      if (!$filesystem->exists($pDest)) {
        throw new AlpdeskCoreFilemanagementException("src does not exists");
      }

      $filesystem->remove($pDest);
    } catch (\Exception $ex) {
      throw new AlpdeskCoreFilemanagementException("error at delete - " . $ex->getMessage());
    }
  }

  public static function rename(string $src, string $target, AlpdescCoreBaseMandantInfo $mandantInfo, bool $accessCheck = true): void {

    if ($accessCheck == true && $mandantInfo->getAccessRename() == false) {
      throw new AlpdeskCoreFilemanagementException("access denied");
    }

    try {

      $pSrc = $mandantInfo->getFilemount_rootpath() . '/' . self::preparePath($src);

      $filesystem = new Filesystem();

      if (!$filesystem->exists($pSrc)) {
        throw new AlpdeskCoreFilemanagementException("src does not exists");
      }

      $pathInfo = \pathinfo($pSrc);
      $pTarget = \str_replace($pathInfo['basename'], self::preparePath($target), $pSrc);

      $filesystem->rename($pSrc, $pTarget, true);
    } catch (\Exception $ex) {
      throw new AlpdeskCoreFilemanagementException("error at rename - " . $ex->getMessage());
    }
  }

  public static function moveOrCopy(string $src, string $target, AlpdescCoreBaseMandantInfo $mandantInfo, bool $copy, bool $accessCheck = true): void {

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

      $pSrc = $mandantInfo->getFilemount_rootpath() . '/' . self::preparePath($src);
      $pTarget = $mandantInfo->getFilemount_rootpath() . '/' . self::preparePath($target);

      $filesystem = new Filesystem();

      if (!$filesystem->exists($pSrc)) {
        throw new AlpdeskCoreFilemanagementException("src does not exists");
      }

      if (!$copy) {
        if (!$filesystem->exists($pTarget)) {
          throw new AlpdeskCoreFilemanagementException("target does not exists");
        }
        if (!\is_dir($pTarget)) {
          throw new AlpdeskCoreFilemanagementException("target is not a directory");
        }
      }

      if (\is_file($pSrc)) {

        $path_parts = \pathinfo($pSrc);
        $newFile = $pTarget . '/' . $path_parts['basename'];

        if ($copy) {
          $newFile = $pTarget;
        }

        if ($pSrc != $newFile) {
          $filesystem->copy($pSrc, $newFile, true);
        } else {
          throw new AlpdeskCoreFilemanagementException("src and target are same");
        }

        if (!$copy) {
          $filesystem->remove($pSrc);
        }
      } else if (\is_dir($pSrc)) {

        $dirname = \basename($pSrc);
        $newFile = $pTarget . '/' . $dirname;

        if ($copy) {
          $newFile = $pTarget;
        }

        if ($pSrc != $newFile) {
          $filesystem->mirror($pSrc, $newFile);
        } else {
          throw new AlpdeskCoreFilemanagementException("src and target are same");
        }

        if (!$copy) {
          $filesystem->remove($pSrc);
        }
      }
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

    if (!\array_key_exists('src', $finderData)) {
      throw new AlpdeskCoreFilemanagementException("invalid key-parameter src for finder");
    }

    $src = (string) $finderData['src'];

    if (!\array_key_exists('target', $finderData)) {
      throw new AlpdeskCoreFilemanagementException("invalid key-parameter target for finder");
    }

    $target = (string) $finderData['target'];

    $mandantInfo = $this->getMandantInformation($user);

    switch ($mode) {
      case 'list': {
          return self::getContentOfDir($src, $mandantInfo);
        }
      case 'create': {
          self::create($src, $target, $mandantInfo);
          return true;
        }
      case 'delete': {
          self::delete($src, $mandantInfo);
          return true;
        }
      case 'rename': {
          self::rename($src, $target, $mandantInfo);
          return true;
        }
      case 'move': {
          self::moveOrcopy($src, $target, $mandantInfo, false);
          return true;
        }
      case 'copy': {
          self::moveOrcopy($src, $target, $mandantInfo, true);
          return true;
        }
      default:
        throw new AlpdeskCoreFilemanagementException("invalid mode for finder");
    }
  }

}
