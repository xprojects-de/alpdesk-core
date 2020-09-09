<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Plugin;

use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;

class AlpdeskCorePlugincallResponse {

  private string $alpdesk_token = '';
  private string $username = '';
  private string $plugin = '';
  private AlpdescCoreBaseMandantInfo $mandantInfo;
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

  public function getPlugin(): string {
    return $this->plugin;
  }

  public function getData(): array {
    return $this->data;
  }

  public function setPlugin(string $plugin): void {
    $this->plugin = $plugin;
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

}
