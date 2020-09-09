<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Listener;

use Contao\CoreBundle\Event\GenerateSymlinksEvent;

class AlpdeskCoreEventContaoGenerateSymlinkListener {

  public function __invoke(GenerateSymlinksEvent $event): void {
    $event->addSymlink(dirname(__DIR__) . '/../../alpdeskclient', 'web/alpdeskclient');
  }

}
