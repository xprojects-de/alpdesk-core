<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Elements\Contao;

use Alpdesk\AlpdeskCore\Library\Database\CrudModel;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreInputSecurity;
use Doctrine\DBAL\Connection;

class ContaoCrud
{
    private array $crudOperations = [
        'schema', 'fetch', 'insert', 'update', 'delete'
    ];

    private bool $useCrudTablePermission = false;
    private ?array $crudTables = null;

    private Connection $connection;
    private array $requestData;

    public function __construct(Connection $connection, array $requestData)
    {
        $this->connection = $connection;
        $this->requestData = $requestData;
    }

    /**
     * @param array|null $crudOperations
     * @return ContaoCrud
     * @throws \Exception
     */
    public function setCrudOperations(?array $crudOperations): ContaoCrud
    {
        if (!\is_array($crudOperations)) {
            throw new \Exception('invalid crudOperations - no permission');
        }

        $this->crudOperations = $crudOperations;
        return $this;
    }

    /**
     * @param array|null $crudTables
     * @return $this
     */
    public function setCrudTables(?array $crudTables): ContaoCrud
    {
        $this->crudTables = $crudTables;
        $this->useCrudTablePermission = true;

        return $this;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function run(): array
    {
        if (
            !\array_key_exists('table', $this->requestData) ||
            !\array_key_exists('crud', $this->requestData) ||
            !\is_string($this->requestData['table']) ||
            $this->requestData['table'] === '' ||
            !\is_string($this->requestData['crud']) ||
            $this->requestData['crud'] === ''
        ) {
            throw new \Exception('invalid requestData');
        }

        $table = AlpdeskcoreInputSecurity::secureValue($this->requestData['table']);
        $crud = AlpdeskcoreInputSecurity::secureValue($this->requestData['crud']);

        if (!\str_starts_with($table, 'tl_')) {
            $table = 'tl_' . $table;
        }

        if ($this->useCrudTablePermission === true) {

            if (!\is_array($this->crudTables) || !\in_array($table, $this->crudTables, true)) {
                throw new \Exception('invalid table - permission denied');
            }

        }

        if (!\in_array($crud, $this->crudOperations, true)) {
            throw new \Exception('invalid crud operation - permission denied');
        }

        $crudModel = new CrudModel();
        $crudModel->setConnection($this->connection);
        $crudModel->setTable($table);

        return $this->runCrud($crudModel, $crud, $this->requestData);

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
        switch ($crudOperation) {

            case 'schema':
            {
                $data = $crudModel->getFields();
                break;
            }

            case 'fetch':
            {
                $select = ['*'];
                if (\array_key_exists('select', $requestData) && \is_array($requestData['select']) && \count($requestData['select']) > 0) {
                    $select = $requestData['select'];
                }

                $limit = null;
                if (\array_key_exists('limit', $requestData) && \is_int($requestData['limit'])) {
                    $limit = $requestData['limit'];
                }

                $offset = null;
                if (\array_key_exists('offset', $requestData) && \is_int($requestData['offset'])) {
                    $offset = $requestData['offset'];
                }

                $orderBy = [];
                if (\array_key_exists('orderBy', $requestData) && \is_array($requestData['orderBy']) && \count($requestData['orderBy']) > 0) {
                    $orderBy = $requestData['orderBy'];
                }

                if (\array_key_exists('where', $requestData) && \is_array($requestData['where']) && \count($requestData['where']) > 0) {
                    $data = $crudModel->fetch($select, $limit, $offset, $orderBy, ...$requestData['where']);
                } else {
                    $data = $crudModel->fetch($select, $limit, $offset, $orderBy);
                }

                break;
            }

            case 'insert':
            {
                if (!\array_key_exists('values', $requestData) || !\is_array($requestData['values']) || \count($requestData['values']) <= 0) {
                    throw new \Exception('invalid values param');
                }

                $insertId = $crudModel->insert($requestData['values']);
                $data = [
                    "id" => (int)$insertId
                ];

                break;
            }

            case 'update':
            {
                if (!\array_key_exists('values', $requestData) || !\is_array($requestData['values']) || \count($requestData['values']) <= 0) {
                    throw new \Exception('invalid values param');
                }

                if (!\array_key_exists('where', $requestData) || !\is_array($requestData['where']) || \count($requestData['where']) <= 0) {
                    throw new \Exception('invalid where param');
                }

                $crudModel->update($requestData['values'], ...$requestData['where']);
                $data = [];

                break;
            }

            case 'delete':
            {
                if (!\array_key_exists('where', $requestData) || !\is_array($requestData['where']) || \count($requestData['where']) <= 0) {
                    throw new \Exception('invalid where param');
                }

                $crudModel->delete(...$requestData['where']);
                $data = [];

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