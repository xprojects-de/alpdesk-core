<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class AlpdeskcoreUser implements UserInterface {

  public static int $ADMIN_MANDANT_ID = 0;
  
  private int $memberId = 0;
  private int $mandantPid = 0;
  private bool $isAdmin = false;
  private $username = '';
  private $password = '';
  private $firstname = '';
  private $lastname = '';
  private $email = '';
  private $token = '';
  private $fixToken = '';
  private $fixTokenAuth = false;
  private $invalidElements = [];
  private $homeDir = null;
  private bool $accessDownload = true;
  private bool $accessUpload = true;
  private bool $accessCreate = true;
  private bool $accessDelete = true;
  private bool $accessRename = true;
  private bool $accessMove = true;
  private bool $accessCopy = true;

  public function getMemberId(): int {
    return $this->memberId;
  }

  public function setMemberId(int $memberId): void {
    $this->memberId = $memberId;
  }

  public function getMandantPid(): int {
    return $this->mandantPid;
  }

  public function setMandantPid(int $mandantPid): void {
    $this->mandantPid = $mandantPid;
  }

  public function getIsAdmin(): bool {
    return $this->isAdmin;
  }

  public function setIsAdmin(bool $isAdmin): void {
    $this->isAdmin = $isAdmin;
  }

  public function getToken() {
    return $this->token;
  }

  public function setToken($token): void {
    $this->token = $token;
  }

  public function setUsername($username): void {
    $this->username = $username;
  }

  public function getUsername() {
    return $this->username;
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

  public function getFixToken() {
    return $this->fixToken;
  }

  public function setFixToken($fixToken): void {
    $this->fixToken = $fixToken;
  }

  public function getFixTokenAuth(): bool {
    return $this->fixTokenAuth;
  }

  public function setFixTokenAuth(bool $fixTokenAuth): void {
    $this->fixTokenAuth = $fixTokenAuth;
  }

  public function getUsedToken(): string {
    if ($this->getFixTokenAuth() == true) {
      return $this->getFixToken();
    }
    return $this->getToken();
  }

  public function getRoles() {
    return array('ROLE_USER');
  }

  public function getPassword(): string {
    return $this->password;
  }

  public function setPassword($password): void {
    $this->password = $password;
  }

  public function getInvalidElements(): array {
    return $this->invalidElements;
  }

  public function setInvalidElements($invalidElements): void {
    $this->invalidElements = $invalidElements;
  }

  public function getHomeDir() {
    return $this->homeDir;
  }

  public function setHomeDir($homeDir): void {
    $this->homeDir = $homeDir;
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

  public function getSalt() {
    
  }

  public function eraseCredentials() {
    
  }

}
