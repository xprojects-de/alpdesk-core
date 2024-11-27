<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;

class CrudModel
{
    private ?Connection $connection = null;
    /** @phpstan-ignore-next-line */
    private ?AbstractSchemaManager $schemaManager = null;
    private ?Schema $schema = null;
    private string $table = '';
    private array $fields = [];

    /**
     * @param Connection|null $connection
     * @throws \Exception
     */
    public function setConnection(?Connection $connection): void
    {
        if ($connection === null) {
            throw new \Exception('connection === null');
        }

        $this->connection = $connection;
    }

    /**
     * @return AbstractSchemaManager
     * @throws \Exception
     * @phpstan-ignore-next-line
     */
    public function getSchemaManager(): AbstractSchemaManager
    {
        if ($this->schemaManager === null) {
            $this->schemaManager = $this->connection->createSchemaManager();
        }

        return $this->schemaManager;
    }

    /**
     * @return Schema
     * @throws \Exception
     */
    public function getSchema(): Schema
    {
        if ($this->schema === null) {
            $this->schema = $this->getSchemaManager()->introspectSchema();
        }

        return $this->schema;
    }

    /**
     * @param string $table
     * @throws \Exception
     */
    public function setTable(string $table): void
    {
        $this->table = $table;

        $this->fields = ['*' => 'string'];

        $columns = $this->getSchemaManager()->listTableColumns($this->table);
        if (\is_array($columns) && \count($columns) > 0) {

            foreach ($columns as $column) {

                if ($column instanceof Column) {

                    $name = $column->getName();
                    $type = $column->getType()->getName();
                    $comment = $column->getComment();

                    if ($name !== null && $name !== '') {

                        if ($comment !== null && $comment !== '') {
                            $type .= ' | ' . $comment;
                        }

                        $this->fields[$name] = $type;
                    }

                }

            }

        }

    }

    /**
     * @param bool $removeStarSelector
     * @return array
     */
    public function getFields(bool $removeStarSelector = true): array
    {
        $fields = $this->fields;

        if ($removeStarSelector === true && \array_key_exists("*", $fields)) {
            unset($fields['*']);
        }

        return $fields;
    }

    /**
     * @param mixed $field
     * @throws \Exception
     */
    private function checkField(mixed $field): void
    {
        if (!\array_key_exists($field, $this->fields)) {
            throw new \Exception('invalid field:' . $field . ' for table');
        }
    }

    /**
     * @return QueryBuilder
     * @throws \Exception
     */
    public function getQueryBuilder(): QueryBuilder
    {
        if ($this->connection === null) {
            throw new \Exception('connection === null');
        }

        return $this->connection->createQueryBuilder();
    }

    public function limit(QueryBuilder $qb, int $limit, int $offset = 0): void
    {
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);
    }

    private function getSQLErrorMessage(string $exMessage): string
    {
        $message = 'invalid statement';

        try {

            $lastMessage = \explode(':', $exMessage);
            if (\count($lastMessage) > 0) {
                $message = \end($lastMessage);
            }

        } catch (\Exception) {
        }

        return \trim($message);

    }

    /**
     * @param array $select
     * @param int|null $limit
     * @param int|null $offset
     * @param array $orderBy
     * @param mixed ...$whereparams
     * @return array
     * @throws \Exception
     */
    public function fetch(array $select, int $limit = null, int $offset = null, array $orderBy = [], mixed ...$whereparams): array
    {
        if ($this->table === '') {
            throw new \Exception('invalid table for CrudModel');
        }

        $selectParam = '*';
        if (\count($select) > 0) {
            foreach ($select as $selectfield) {
                $this->checkField($selectfield);
            }
            $selectParam = \implode(',', $select);
        }

        $qb = $this->getQueryBuilder()->select($selectParam)->from($this->table);

        if (\count($whereparams) > 0) {

            $qb->where($whereparams[0]);
            \array_shift($whereparams);

            $counter = 0;
            foreach ($whereparams as $wparam) {
                $qb->setParameter($counter, $wparam);
                $counter++;
            }

        }

        if (\count($orderBy)) {

            foreach ($orderBy as $key => $value) {
                $qb->addOrderBy($key, ($value !== '' ? $value : 'ASC'));
            }

        }

        if ($limit !== null && $limit > 0) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null && $offset >= 0) {
            $qb->setFirstResult($offset);
        }

        try {
            $data = $qb->executeQuery()->fetchAllAssociative();
        } catch (\Exception $ex) {
            throw new \Exception($this->getSQLErrorMessage($ex->getMessage()));
        }

        if (\count($data) === 1) {
            return $data[0];
        }

        return $data;
    }

    /**
     * @param array $values
     * @param mixed ...$whereparams
     * @throws \Exception
     */
    public function update(array $values, mixed ...$whereparams): void
    {
        if ($this->table === '') {
            throw new \Exception('invalid table for CrudModel');
        }

        if (\array_key_exists('id', $values)) {
            unset($values['id']);
        }

        $qb = $this->getQueryBuilder()->update($this->table);

        $counter = 0;

        foreach ($values as $key => $value) {

            try {

                $qb->set($key, '?')->setParameter($counter, $value);
                $counter++;

            } catch (\Exception $ex) {
                throw new \Exception($ex->getMessage());
            }

        }

        if (\count($whereparams) > 0) {

            $qb->where($whereparams[0]);
            \array_shift($whereparams);

            foreach ($whereparams as $wparam) {

                $qb->setParameter($counter, $wparam);
                $counter++;

            }

        }

        try {
            $qb->executeStatement();
        } catch (\Exception $ex) {
            throw new \Exception($this->getSQLErrorMessage($ex->getMessage()));
        }

    }

    /**
     * @param array $values
     * @return string
     * @throws \Exception
     */
    public function insert(array $values): string
    {
        if ($this->table === '') {
            throw new \Exception('invalid table for CrudModel');
        }

        if (\array_key_exists('id', $values)) {
            unset($values['id']);
        }

        $qb = $this->getQueryBuilder()->insert($this->table);

        $counter = 0;

        foreach ($values as $key => $value) {

            try {

                $qb->setValue($key, '?')->setParameter($counter, $value);
                $counter++;

            } catch (\Exception $ex) {
                throw new \Exception($ex->getMessage());

            }
        }

        try {
            $qb->executeStatement();
        } catch (\Exception $ex) {
            throw new \Exception($this->getSQLErrorMessage($ex->getMessage()));
        }

        return $this->connection->lastInsertId();
    }


    /**
     * @param mixed ...$whereparams
     * @throws \Exception
     */
    public function delete(mixed ...$whereparams): void
    {
        if ($this->table === '') {
            throw new \Exception('invalid table for CrudModel');
        }

        $qb = $this->getQueryBuilder()->delete($this->table);

        $counter = 0;
        if (\count($whereparams) > 0) {

            $qb->where($whereparams[0]);
            \array_shift($whereparams);

            foreach ($whereparams as $wparam) {

                $qb->setParameter($counter, $wparam);
                $counter++;

            }

        } else {
            throw new \Exception('delete must have a where-Clause');
        }

        try {
            $qb->executeStatement();
        } catch (\Exception $ex) {
            throw new \Exception($this->getSQLErrorMessage($ex->getMessage()));
        }

    }

}
