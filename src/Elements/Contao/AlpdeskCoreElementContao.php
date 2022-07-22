<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Elements\Contao;

use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCorePlugincallEvent;
use Alpdesk\AlpdeskCore\Library\Database\CrudModel;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreInputSecurity;
use Doctrine\DBAL\Connection;

class AlpdeskCoreElementContao
{
    private static array $CRUD_OPERATIONS = [
        'fetch'
    ];
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

        $requestData = $event->getResultData()->getRequestData();
        if (
            !\array_key_exists('table', $requestData) ||
            !\array_key_exists('crud', $requestData) ||
            !\is_string($requestData['table']) ||
            $requestData['table'] === '' ||
            !\is_string($requestData['crud']) ||
            $requestData['crud'] === ''
        ) {
            throw new \Exception('invalid requestData');
        }

        $table = AlpdeskcoreInputSecurity::secureValue($requestData['table']);
        $crud = AlpdeskcoreInputSecurity::secureValue($requestData['crud']);

        if (!\str_starts_with($table, 'tl_')) {
            $table = 'tl_' . $table;
        }

        if (!\in_array($crud, self::$CRUD_OPERATIONS, true)) {
            throw new \Exception('invalid crud operation');
        }

        $crudModel = new CrudModel();
        $crudModel->setConnection($this->connection);
        $crudModel->setTable($table);

        $event->getResultData()->setData($this->runCrud($crudModel, $crud, $requestData));

    }

    /**
     * @param CrudModel $crudModel
     * @param string $crudOperation
     * @param array $requestData
     * @return array
     * @throws \Exception
     */
    private function runCrud(CrudModel $crudModel, string $crudOperation, array $requestData): array
    {
        $data = [];

        switch ($crudOperation) {

            case 'fetch':
            {
                $select = ['*'];
                if (\array_key_exists('select', $requestData) && \is_array($requestData['select']) && \count($requestData['select']) > 0) {
                    $select = $requestData['select'];
                }

                $limit = null;
                if (\array_key_exists('limit', $requestData) && \is_int($requestData['limit']) && $requestData['limit'] !== 0) {
                    $limit = $requestData['limit'];
                }

                $offset = null;
                if (\array_key_exists('offset', $requestData) && \is_int($requestData['offset']) && $requestData['offset'] !== 0) {
                    $offset = $requestData['offset'];
                }

                $data = $crudModel->fetch($select, $limit, $offset);
                break;
            }

            default:
            {
                throw new \Exception('invalid crud operation');
            }

        }

        return $data;
    }

}
