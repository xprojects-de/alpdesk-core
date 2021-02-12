<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class AlpdeskcoreUser implements UserInterface {

  private int $memberId = 0;
  private int $mandantPid = 0;
  private $username = '';
  private $password = '';
  private $firstname = '';
  private $lastname = '';
  private $email = '';
  private $token = '';
  private $fixToken = '';
  private $fixTokenAuth = false;
  private $invalidElements = [];

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

  public function getSalt() {
    
  }

  public function eraseCredentials() {
    
  }

}
