<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Callbacks;

use Contao\DataContainer;
use Contao\Image;
use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreRegisterPlugin;

class DcaCallbacks {

  protected AlpdeskCoreEventService $eventService;

  public function __construct(AlpdeskCoreEventService $eventService) {
    $this->eventService = $eventService;
  }

  private function getLegacyElements(): array {

    $data = [];

    if (isset($GLOBALS['TL_ADME']) && \count($GLOBALS['TL_ADME'])) {
      foreach ($GLOBALS['TL_ADME'] as $k => $v) {
        $data[$k] = $GLOBALS['TL_LANG']['ADME'][$k];
      }
    }

    return $data;
  }

  public function getMandantElements(DataContainer $dc) {

    $event = new AlpdeskCoreRegisterPlugin($this->getLegacyElements(), []);
    $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreRegisterPlugin::NAME);

    return $event->getPluginData();
  }

  public function addMandantElementType($arrRow): string {

    $event = new AlpdeskCoreRegisterPlugin($this->getLegacyElements(), []);
    $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreRegisterPlugin::NAME);

    $dataComplete = $event->getPluginData();

    $key = $arrRow['disabled'] ? 'unpublished' : 'published';
    $icon = (($arrRow['invisible'] | $arrRow['disabled']) ? 'invisible.svg' : 'visible.svg');
    $type = $dataComplete[$arrRow['type']] ?: '- INVALID -';

    return '<div class="cte_type ' . $key . '">' . Image::getHtml($icon) . '&nbsp;&nbsp;' . $type . '</div>';
  }

}
