<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AlpdeskCoreEventService {

  protected $dispatcher;

  public function __construct(EventDispatcherInterface $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  public function getDispatcher(): EventDispatcherInterface {
    return $this->dispatcher;
  }

}
