<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Database;

use Contao\Model;
use Alpdesk\AlpdeskCore\Library\Cryption\Cryption;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;

class AlpdeskcoreDatabasemanagerModel extends Model
{
    protected static $strTable = 'tl_alpdeskcore_databasemanager';
    private static array $connectionsTable = [];

    private static function create(int $id, string $host, int $port, string $username, string $password, string $database): ?Connection
    {
        if (\count(self::$connectionsTable) > 0 && \array_key_exists($id, self::$connectionsTable)) {

            if (self::$connectionsTable[$id] instanceof Connection) {

                if (self::$connectionsTable[$id] !== null && self::$connectionsTable[$id]->isConnected()) {
                    return self::$connectionsTable[$id];
                }

            }

        }

        $params = [
            'driver' => 'pdo_mysql',
            'host' => $host,
            'port' => $port,
            'user' => $username,
            'password' => $password,
            'dbname' => $database,
            'charset' => 'utf8mb4'
        ];

        try {

            self::$connectionsTable[$id] = DriverManager::getConnection($params);

            if (!self::$connectionsTable[$id]->isConnected()) {
                self::$connectionsTable[$id]->connect();
            }

            return self::$connectionsTable[$id];

        } catch (\Exception $e) {

        }

        return null;
    }

    public static function destroy(int $id): void
    {
        if (\array_key_exists($id, self::$connectionsTable)) {

            if (self::$connectionsTable[$id] instanceof Connection) {

                if (self::$connectionsTable[$id] !== null) {

                    self::$connectionsTable[$id]->close();
                    self::$connectionsTable[$id] = null;

                }

            }

        }
    }

    /**
     * @param int $id
     * @param string|null $name
     * @return array
     * @throws \Exception
     */
    public static function listTables(int $id, ?string $name): array
    {
        if (null === $name || !\array_key_exists($id, self::$connectionsTable)) {
            throw new \Exception($GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['invalid_parameters']);
        }

        if (self::$connectionsTable[$id] === null || !self::$connectionsTable[$id] instanceof Connection) {
            throw new \Exception($GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['invalid_parameters']);
        }

        try {

            $tables = self::$connectionsTable[$id]->createSchemaManager()->createSchema()->getTables();

            $structure = array();
            foreach ($tables as $table) {
                if ($table instanceof Table) {

                    $options = "";
                    $tableOptions = $table->getOptions();

                    if (\is_array($tableOptions) && \count($tableOptions) > 0) {

                        if (\array_key_exists('engine', $tableOptions)) {
                            $options .= 'engine: ' . $tableOptions['engine'];
                        }

                        if (\array_key_exists('collation', $tableOptions)) {
                            $options .= '<br>collation: ' . $tableOptions['collation'];
                        }

                        if (\array_key_exists('autoincrement', $tableOptions)) {
                            $options .= '<br>autoincrement: ' . $tableOptions['autoincrement'];
                        }

                    }

                    $primaryKey = array();

                    $pKey = $table->getPrimaryKey();
                    if ($pKey !== null) {
                        foreach ($pKey->getColumns() as $column) {
                            $primaryKey[] = $column;
                        }
                    }

                    $indexes = array();
                    foreach ($table->getIndexes() as $indexEntry) {

                        if ('PRIMARY' !== $indexEntry->getName()) {

                            $indexInfo = array(
                                'indexunique' => $indexEntry->isUnique(),
                                'indexfields' => '',
                                'indexname' => $indexEntry->getName()
                            );

                            $tmpIndexFields = array();
                            foreach ($indexEntry->getColumns() as $column) {
                                $tmpIndexFields[] = $column;
                            }

                            $indexInfo['indexfields'] = \implode(',', $tmpIndexFields);
                            $indexes[] = $indexInfo;
                        }

                    }

                    $indiciesStringArray = array();
                    if (\is_array($indexes) && \count($indexes) > 0) {
                        foreach ($indexes as $ind) {
                            $indiciesStringArray[] = 'Name: ' . $ind['indexname'] . ', Unique: ' . ($ind['indexunique'] === true ? 'true' : 'false') . ', Fields: ' . $ind['indexfields'];
                        }
                    }

                    $structure[$table->getName()] = array(
                        'options' => $options,
                        'primary' => \implode('<br>', $primaryKey),
                        'indexes' => \implode('<br>', $indiciesStringArray)
                    );

                    $columns = $table->getColumns();
                    if (\is_array($columns) && \count($columns) > 0) {

                        foreach ($columns as $column) {

                            if ($column instanceof Column) {

                                $type = $column->getType();
                                $output = $type->getName();

                                if ($column->getAutoincrement()) {
                                    $output .= ' | autoincrement';
                                }

                                if ($column->getUnsigned()) {
                                    $output .= ' | unsigned';
                                }

                                if ($column->getNotnull()) {
                                    $output .= ' | NOT NULL';
                                }

                                $default = $column->getDefault();
                                if ($default !== null) {
                                    $output .= ' | DEFAULT "' . $default . '"';
                                }

                                $length = $column->getLength();
                                if ($length !== null) {
                                    $output .= ' | LENGTH ' . $length;
                                }

                                $platformOptions = $column->getPlatformOptions();
                                if (\is_array($platformOptions) && \count($platformOptions) > 0) {
                                    foreach ($platformOptions as $pKey => $pValue) {
                                        $output .= ' | ' . $pKey . ' ' . $pValue;
                                    }
                                }

                                $structure[$table->getName()][$column->getName()] = $output;
                            }
                        }
                    }
                }
            }

            return $structure;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param int $id
     * @return Connection|null
     * @throws \Exception
     */
    public static function connectionById(int $id): ?Connection
    {
        if (\count(self::$connectionsTable) > 0 && \array_key_exists($id, self::$connectionsTable)) {

            if (self::$connectionsTable[$id] instanceof Connection) {

                if (self::$connectionsTable[$id] !== null && self::$connectionsTable[$id]->isConnected()) {
                    return self::$connectionsTable[$id];
                }

            }

        }

        $result = self::findByPk($id);

        if ($result !== null) {

            $decryption = new Cryption(true);
            $result->password = $decryption->safeDecrypt($result->password);

            return self::create($id, $result->host, (int)$result->port, $result->username, $result->password, $result->database);
        }

        return null;

    }
}
