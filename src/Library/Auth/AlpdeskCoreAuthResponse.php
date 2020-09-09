<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Auth;

class AlpdeskCoreAuthResponse {

  private string $alpdesk_token = '';
  private string $username = '';
  private bool $verify = false;
  private bool $invalid = true;

  public function getAlpdesk_token(): string {
    return $this->alpdesk_token;
  }

  public function getUsername(): string {
    return $this->username;
  }

  public function getVerify(): bool {
    return $this->verify;
  }

  public function getInvalid(): bool {
    return $this->invalid;
  }

  public function setAlpdesk_token(string $alpdesk_token): void {
    $this->alpdesk_token = $alpdesk_token;
  }

  public function setUsername(string $username): void {
    $this->username = $username;
  }

  public function setVerify(bool $verify): void {
    $this->verify = $verify;
  }

  public function setInvalid(bool $invalid): void {
    $this->invalid = $invalid;
  }

}
