<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Mandant;

class AlpdeskCoreMandantResponse {

  private string $alpdesk_token = '';
  private string $username = '';
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

}
