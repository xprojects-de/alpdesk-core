<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Mandant;

class AlpdeskCoreMandantResponse {

  private string $alpdesk_token = '';
  private string $username = '';
  private $firstname = '';
  private $lastname = '';
  private $email = '';
  private bool $accessDownload = true;
  private bool $accessUpload = true;
  private bool $accessCreate = true;
  private bool $accessDelete = true;
  private bool $accessRename = true;
  private bool $accessMove = true;
  private bool $accessCopy = true;
  private int $memberId = 0;
  private int $mandantId = 0;
  private array $plugins = array();
  private array $data = array();

  public function getAlpdesk_token(): string {
    return $this->alpdesk_token;
  }

  public function getUsername(): string {
    return $this->username;
  }

  public function getMandantId(): int {
    return $this->mandantId;
  }

  public function getPlugins(): array {
    return $this->plugins;
  }

  public function getData(): array {
    return $this->data;
  }

  public function setUsername(string $username): void {
    $this->username = $username;
  }

  public function setAlpdesk_token(string $alpdesk_token): void {
    $this->alpdesk_token = $alpdesk_token;
  }

  public function setMandantId(int $mandantId): void {
    $this->mandantId = $mandantId;
  }

  public function setPlugins(array $plugins): void {
    $this->plugins = $plugins;
  }

  public function setData(array $data): void {
    $this->data = $data;
  }

  public function getMemberId(): int {
    return $this->memberId;
  }

  public function setMemberId(int $memberId): void {
    $this->memberId = $memberId;
  }

  public function getFirstname() {
    return $this->firstname;
  }

  public function getLastname() {
    return $this->lastname;
  }

  public function getEmail() {
    return $this->email;
  }

  public function setFirstname($firstname): void {
    $this->firstname = $firstname;
  }

  public function setLastname($lastname): void {
    $this->lastname = $lastname;
  }

  public function setEmail($email): void {
    $this->email = $email;
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
