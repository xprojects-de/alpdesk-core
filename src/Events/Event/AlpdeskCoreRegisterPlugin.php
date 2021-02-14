<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Event;

use Symfony\Contracts\EventDispatcher\Event;

class AlpdeskCoreRegisterPlugin extends Event {

  public const NAME = 'alpdesk.registerplugin';

  private array $pluginData = [];
  private array $pluginInfo = [];

  public function __construct(array $pluginData, array $pluginInfo) {
    $this->pluginData = $pluginData;
    $this->pluginInfo = $pluginInfo;
  }

  public function getPluginData(): array {
    return $this->pluginData;
  }

  public function getPluginInfo(): array {
    return $this->pluginInfo;
  }

  public function setPluginData(array $pluginData): void {
    $this->pluginData = $pluginData;
  }

  public function setPluginInfo(array $pluginInfo): void {
    $this->pluginInfo = $pluginInfo;
  }

}
