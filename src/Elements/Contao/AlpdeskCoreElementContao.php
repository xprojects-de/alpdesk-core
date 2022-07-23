<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Elements\Contao;

use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCorePlugincallEvent;
use Doctrine\DBAL\Connection;

class AlpdeskCoreElementContao
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param AlpdeskCorePlugincallEvent $event
     * @return void
     * @throws \Exception
     */
    public function __invoke(AlpdeskCorePlugincallEvent $event): void
    {
        if ('contaoCrud' !== $event->getResultData()->getPlugin()) {
            return;
        }

        $event->getResultData()->setData(
            (new ContaoCrud($this->connection, $event->getResultData()->getRequestData()))->run()
        );

    }

}
