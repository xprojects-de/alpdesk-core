<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Mandant;

class AlpdescCoreBaseMandantInfo {

  private int $id;
  private int $memberId;
  private string $mandant;
  private string $filemountmandant_uuid;
  private string $filemountmandant_path;
  private string $filemountmandant_rootpath;
  private string $filemount_uuid;
  private string $filemount_path;
  private string $filemount_rootpath;
  private bool $accessDownload = true;
  private bool $accessUpload = true;
  private bool $accessCreate = true;
  private bool $accessDelete = true;
  private bool $accessRename = true;
  private bool $accessMove = true;
  private bool $accessCopy = true;
  private array $additionalDatabaseInformation;

  public function getId(): int {
    return $this->id;
  }

  public function getMandant(): string {
    return $this->mandant;
  }

  public function getFilemountmandant_uuid(): string {
    return $this->filemountmandant_uuid;
  }

  public function setFilemountmandant_uuid(string $filemountmandant_uuid): void {
    $this->filemountmandant_uuid = $filemountmandant_uuid;
  }

  public function getFilemountmandant_path(): string {
    return $this->filemountmandant_path;
  }

  public function getFilemountmandant_rootpath(): string {
    return $this->filemountmandant_rootpath;
  }

  public function setFilemountmandant_path(string $filemountmandant_path): void {
    $this->filemountmandant_path = $filemountmandant_path;
  }

  public function setFilemountmandant_rootpath(string $filemountmandant_rootpath): void {
    $this->filemountmandant_rootpath = $filemountmandant_rootpath;
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

  public function getMemberId(): int {
    return $this->memberId;
  }

  public function setMemberId(int $memberId): void {
    $this->memberId = $memberId;
  }

  public function getAccessDownload(): bool {
    return $this->accessDownload;
  }

  public function getAccessUpload(): bool {
    return $this->accessUpload;
  }

  public function getAccessCreate(): bool {
    return $this->accessCreate;
  }

  public function getAccessDelete(): bool {
    return $this->accessDelete;
  }

  public function getAccessRename(): bool {
    return $this->accessRename;
  }

  public function getAccessMove(): bool {
    return $this->accessMove;
  }

  public function getAccessCopy(): bool {
    return $this->accessCopy;
  }

  public function setAccessDownload(bool $accessDownload): void {
    $this->accessDownload = $accessDownload;
  }

  public function setAccessUpload(bool $accessUpload): void {
    $this->accessUpload = $accessUpload;
  }

  public function setAccessCreate(bool $accessCreate): void {
    $this->accessCreate = $accessCreate;
  }

  public function setAccessDelete(bool $accessDelete): void {
    $this->accessDelete = $accessDelete;
  }

  public function setAccessRename(bool $accessRename): void {
    $this->accessRename = $accessRename;
  }

  public function setAccessMove(bool $accessMove): void {
    $this->accessMove = $accessMove;
  }

  public function setAccessCopy(bool $accessCopy): void {
    $this->accessCopy = $accessCopy;
  }

}
