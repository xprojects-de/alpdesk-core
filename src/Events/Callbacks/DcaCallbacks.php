<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Callbacks;

use Contao\DataContainer;
use Contao\Image;
use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreRegisterPlugin;

class DcaCallbacks
{
    protected AlpdeskCoreEventService $eventService;

    /**
     * @param AlpdeskCoreEventService $eventService
     */
    public function __construct(AlpdeskCoreEventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * @return array
     */
    private function getLegacyElements(): array
    {
        $data = [];

        if (isset($GLOBALS['TL_ADME']) && \count($GLOBALS['TL_ADME'])) {
            foreach ($GLOBALS['TL_ADME'] as $k => $v) {
                $data[$k] = $GLOBALS['TL_LANG']['ADME'][$k];
            }
        }

        return $data;
    }

    /**
     * @param DataContainer|null $dc
     * @return array
     */
    public function getMandantElements(?DataContainer $dc): array
    {
        $event = new AlpdeskCoreRegisterPlugin($this->getLegacyElements(), []);
        $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreRegisterPlugin::NAME);

        return $event->getPluginData();
    }

    /**
     * @param array $arrRow
     * @return string
     */
    public function addMandantElementType(array $arrRow): string
    {
        $event = new AlpdeskCoreRegisterPlugin($this->getLegacyElements(), []);
        $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreRegisterPlugin::NAME);

        $dataComplete = $event->getPluginData();

        $key = $arrRow['disabled'] ? 'unpublished' : 'published';
        $icon = (($arrRow['invisible'] | $arrRow['disabled']) ? 'invisible.svg' : 'visible.svg');
        $type = $dataComplete[$arrRow['type']] ?: '- INVALID -';

        return '<div class="cte_type ' . $key . '">' . Image::getHtml($icon) . '&nbsp;&nbsp;' . $type . '</div>';
    }

    /**
     * @param DataContainer|null $dc
     */
    public function databaseManagerOnLoad(DataContainer $dc = null): void
    {
        $GLOBALS['TL_CSS'][] = 'bundles/alpdeskcore/css/alpdeskcore_widget_databasemanager.css';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/alpdeskcore/js/alpdeskcore_widget_databasemanager.js';
    }

}
