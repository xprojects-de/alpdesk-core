<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Mandant;

class AlpdescCoreBaseMandantInfo {

  private int $id;
  private string $mandant;
  private string $filemount_uuid;
  private string $filemount_path;
  private string $filemount_rootpath;
  private array $additionalDatabaseInformation;

  public function getId(): int {
    return $this->id;
  }

  public function getMandant(): string {
    return $this->mandant;
  }

  public function getFilemount_uuid(): string {
    return $this->filemount_uuid;
  }

  public function getFilemount_path(): string {
    return $this->filemount_path;
  }

  public function setId(int $id): void {
    $this->id = $id;
  }

  public function setMandant(string $mandant): void {
    $this->mandant = $mandant;
  }

  public function setFilemount_uuid(string $filemount_uuid): void {
    $this->filemount_uuid = $filemount_uuid;
  }

  public function setFilemount_path(string $filemount_path): void {
    $this->filemount_path = $filemount_path;
  }

  public function getFilemount_rootpath(): string {
    return $this->filemount_rootpath;
  }

  public function setFilemount_rootpath(string $filemount_rootpath): void {
    $this->filemount_rootpath = $filemount_rootpath;
  }

  public function getAdditionalDatabaseInformation(): array {
    return $this->additionalDatabaseInformation;
  }

  public function setAdditionalDatabaseInformation(array $additionalDatabaseInformation): void {
    $this->additionalDatabaseInformation = $additionalDatabaseInformation;
  }

}
