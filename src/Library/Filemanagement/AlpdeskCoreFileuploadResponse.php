<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Filemanagement;

use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;

class AlpdeskCoreFileuploadResponse {

  private string $alpdesk_token = '';
  private string $username = '';
  private AlpdescCoreBaseMandantInfo $mandantInfo;
  private string $fileName = '';
  private string $rootFileName = '';
  private array $data = array();

  public function getAlpdesk_token(): string {
    return $this->alpdesk_token;
  }

  public function getUsername(): string {
    return $this->username;
  }

  public function setAlpdesk_token(string $alpdesk_token): void {
    $this->alpdesk_token = $alpdesk_token;
  }

  public function setUsername(string $username): void {
    $this->username = $username;
  }

  public function getData(): array {
    return $this->data;
  }

  public function setData(array $data): void {
    $this->data = $data;
  }

  public function getMandantInfo(): AlpdescCoreBaseMandantInfo {
    return $this->mandantInfo;
  }

  public function setMandantInfo(AlpdescCoreBaseMandantInfo $mandantInfo): void {
    $this->mandantInfo = $mandantInfo;
  }

  public function getFileName(): string {
    return $this->fileName;
  }

  public function setFileName(string $fileName): void {
    $this->fileName = $fileName;
  }

  public function getRootFileName(): string {
    return $this->rootFileName;
  }

  public function setRootFileName(string $rootFileName): void {
    $this->rootFileName = $rootFileName;
  }

}
