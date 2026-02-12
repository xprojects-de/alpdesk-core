<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Database;

use Contao\Model;
use Alpdesk\AlpdeskCore\Library\Cryption\Cryption;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class AlpdeskcoreDatabasemanagerModel extends Model
{
    protected static $strTable = 'tl_alpdeskcore_databasemanager';
    private static array $connectionsTable = [];

    private static function create(int $id, string $host, int $port, string $username, string $password, string $database): ?Connection
    {
        if (
            \array_key_exists($id, self::$connectionsTable) &&
            self::$connectionsTable[$id] instanceof Connection &&
            self::$connectionsTable[$id]->isConnected()
        ) {
            return self::$connectionsTable[$id];
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
                self::$connectionsTable[$id]->executeQuery('SELECT 1');
            }

            return self::$connectionsTable[$id];

        } catch (\Throwable) {

        }

        return null;
    }

    public static function destroy(int $id): void
    {
        if (
            \array_key_exists($id, self::$connectionsTable) &&
            self::$connectionsTable[$id] instanceof Connection
        ) {

            self::$connectionsTable[$id]->close();
            self::$connectionsTable[$id] = null;

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

            $tables = self::$connectionsTable[$id]->createSchemaManager()->introspectSchema()->getTables();

            $structure = array();
            foreach ($tables as $table) {
                if ($table instanceof Table) {

                    $options = "";
                    $tableOptions = $table->getOptions();

                    if (\count($tableOptions) > 0) {

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

                    $indiciesStringArray = [];

                    if (\count($indexes) > 0) {

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
                    if (\count($columns) > 0) {

                        foreach ($columns as $column) {

                            $output = strtolower(Type::getTypeRegistry()->lookupName($column->getType()));

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
                            if (\count($platformOptions) > 0) {

                                foreach ($platformOptions as $pKey => $pValue) {
                                    $output .= ' | ' . $pKey . ' ' . $pValue;
                                }

                            }

                            $structure[$table->getName()][$column->getName()] = $output;

                        }
                    }
                }
            }

            return $structure;

        } catch (\Throwable $e) {
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
        if (
            \array_key_exists($id, self::$connectionsTable) &&
            self::$connectionsTable[$id] instanceof Connection &&
            self::$connectionsTable[$id]->isConnected()
        ) {
            return self::$connectionsTable[$id];
        }

        $result = self::findByPk($id);

        if ($result !== null) {

            $decryption = new Cryption(true);
            $password = $decryption->safeDecrypt($result->password);

            return self::create($id, $result->host, (int)$result->port, $result->username, $password, $result->database);
        }

        return null;

    }

}
