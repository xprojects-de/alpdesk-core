<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Table;

class AlpdeskcoreMigration
{
    private Connection $connection;
    private array $model;

    public function __construct(Connection $connection, array $model)
    {
        $this->connection = $connection;
        $this->model = $model;
    }

    /**
     * @param array $commands
     * @throws \Exception
     */
    public function executeMigrations(array $commands): void
    {
        foreach ($commands as $command) {
            $this->connection->executeQuery($command);
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function showMigrations(): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $fromSchema = $schemaManager->introspectSchema();

        $databasePlatForm = $this->connection->getDatabasePlatform();

        if (!$databasePlatForm instanceof AbstractPlatform) {
            throw new \Exception('invalid DatabasePlatform');
        }

        return $databasePlatForm->getAlterSchemaSQL((new Comparator())->compareSchemas($fromSchema, $this->parseSql()));

    }

    /**
     * @return Schema
     * @throws \Exception
     */
    private function parseSql(): Schema
    {
        if (\count($this->model) > 0) {

            $schemaConfig = new SchemaConfig();
            $defaultOptions = $schemaConfig->getDefaultTableOptions();

            if (\array_key_exists('charset', $this->model)) {
                $defaultOptions['charset'] = $this->model['charset'];
            }

            if (\array_key_exists('collation', $this->model)) {
                $defaultOptions['collate'] = $this->model['collation'];
            }

            if (\array_key_exists('engine', $this->model)) {
                $defaultOptions['engine'] = $this->model['engine'];
            }

            $schemaConfig->setDefaultTableOptions($defaultOptions);
            $schema = new Schema([], [], $schemaConfig);

            if (\array_key_exists('tables', $this->model)) {

                foreach ($this->model['tables'] as $currentTable) {

                    $table = $schema->createTable($currentTable['table']);

                    if (\array_key_exists('fields', $currentTable)) {

                        foreach ($currentTable['fields'] as $field => $fieldattributes) {

                            if (\is_array($fieldattributes)) {
                                $this->parseField($table, $field, $fieldattributes);
                            }
                        }

                    }

                    if (\array_key_exists('primary', $currentTable) && \is_array($currentTable['primary'])) {
                        $table->setPrimaryKey($currentTable['primary']);
                    }

                    if (\array_key_exists('index', $currentTable) && \is_array($currentTable['index'])) {

                        foreach ($currentTable['index'] as $indexname => $indexfields) {

                            if (\is_array($indexfields)) {
                                $table->addIndex($indexfields, $indexname);
                            }

                        }

                    }

                    if (\array_key_exists('foreignKeys', $currentTable) && \is_array($currentTable['foreignKeys'])) {

                        foreach ($currentTable['foreignKeys'] as $foreignTable => $columnMatching) {

                            if (\is_array($columnMatching) && \count($columnMatching) > 0) {

                                // avalible RESTRICT, CASCADE, NO ACTION
                                $options = [
                                    'onDelete' => 'RESTRICT',
                                    'onUpdate' => 'RESTRICT'
                                ];

                                if (\array_key_exists('onDelete', $columnMatching)) {
                                    $options['onDelete'] = $columnMatching['onDelete'];
                                }

                                if (\array_key_exists('onUpdate', $columnMatching)) {
                                    $options['onUpdate'] = $columnMatching['onUpdate'];
                                }

                                if (
                                    \array_key_exists('constraint', $columnMatching) &&
                                    \is_array($columnMatching['constraint']) && \count($columnMatching['constraint']) > 0
                                ) {

                                    // Only one per Table // Maybe @TODO
                                    $cCounter = 0;
                                    foreach ($columnMatching['constraint'] as $localColumn => $foreignColumn) {

                                        $table->addForeignKeyConstraint($foreignTable, [$localColumn], [$foreignColumn], $options);
                                        $cCounter++;

                                        if ($cCounter > 0) {
                                            break;
                                        }

                                    }

                                }

                            }

                        }

                    }

                    if (\array_key_exists('unique', $currentTable) && \is_array($currentTable['unique'])) {

                        $uniqueValueArray = $currentTable['unique'];
                        foreach ($uniqueValueArray as $uValue) {

                            if (\is_array($uValue)) {
                                $table->addUniqueIndex($uValue);
                            }

                        }

                    }

                }

            }

            return $schema;

        }

        throw new \Exception('invalid database model');

    }

    /**
     * @param Table $table
     * @param string $field
     * @param array $fieldattributes
     * @throws \Exception
     */
    private function parseField(Table $table, string $field, array $fieldattributes): void
    {
        $dbType = $fieldattributes['type'];

        $length = null;
        if (\array_key_exists('length', $fieldattributes)) {
            $length = (int)$fieldattributes['length'];
            $dbType = $fieldattributes['type'] . '(' . $length . ')';
        }

        $fixed = false;
        $scale = null;
        $precision = null;
        $unsigned = false;

        if (
            \array_key_exists('unsigned', $fieldattributes) &&
            $fieldattributes['unsigned'] === true &&
            \in_array(strtolower($fieldattributes['type']), array('tinyint', 'smallint', 'mediumint', 'int', 'bigint'))
        ) {
            $unsigned = true;
        }

        $autoincrement = false;
        if (\array_key_exists('autoincrement', $fieldattributes) && $fieldattributes['autoincrement'] === true) {
            $autoincrement = true;
        }

        $default = null;
        if (\array_key_exists('default', $fieldattributes)) {

            $default = $fieldattributes['default'];
            if ($autoincrement === true || $default === 'NULL') {
                $default = null;
            }

        }

        $this->setLengthAndPrecisionByType($fieldattributes['type'], $dbType, $length, $scale, $precision, $fixed);

        $type = $this->connection->getDatabasePlatform()?->getDoctrineTypeMapping($fieldattributes['type']);
        if ($type === null) {
            throw new \Exception('invalid type for ' . $fieldattributes['type']);
        }

        if (0 === $length) {
            $length = null;
        }

        $notNull = false;
        if (\array_key_exists('notnull', $fieldattributes)) {
            $notNull = $fieldattributes['notnull'];
        }

        $comment = $fieldattributes['comment'] ?? null;

        $options = [
            'length' => $length,
            'unsigned' => $unsigned,
            'fixed' => $fixed,
            'default' => $default,
            'notnull' => $notNull,
            'scale' => null,
            'precision' => null,
            'autoincrement' => $autoincrement,
            'comment' => $comment,
        ];

        if (null !== $scale && null !== $precision) {

            $options['scale'] = $scale;
            $options['precision'] = $precision;

        }

        $platformOptions = [];

        $collation = $fieldattributes['collation'] ?? null;
        $charset = $fieldattributes['charset'] ?? null;

        if (null !== $charset) {
            $platformOptions['charset'] = $charset;
        }

        if (null !== $collation) {
            $platformOptions['collation'] = $collation;
        }

        if (!empty($platformOptions)) {
            $options['platformOptions'] = $platformOptions;
        }

        $table->addColumn($field, $type, $options);

    }

    private function setLengthAndPrecisionByType(string $type, string $dbType, ?int &$length, ?int &$scale, ?int &$precision, bool &$fixed): void
    {
        switch ($type) {
            case 'char':
            case 'binary':
                $fixed = true;
                break;

            case 'float':
            case 'double':
            case 'real':
            case 'numeric':
            case 'decimal':
                if (\preg_match('/[a-z]+\((\d+),(\d+)\)/i', $dbType, $match)) {
                    $length = null;
                    [, $precision, $scale] = $match;
                }
                break;

            case 'tinytext':
                $length = AbstractMySQLPlatform::LENGTH_LIMIT_TINYTEXT;
                break;

            case 'text':
                $length = AbstractMySQLPlatform::LENGTH_LIMIT_TEXT;
                break;

            case 'mediumtext':
                $length = AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT;
                break;

            case 'tinyblob':
                $length = AbstractMySQLPlatform::LENGTH_LIMIT_TINYBLOB;
                break;

            case 'blob':
                $length = AbstractMySQLPlatform::LENGTH_LIMIT_BLOB;
                break;

            case 'mediumblob':
                $length = AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB;
                break;

            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
            case 'year':
                $length = null;
                break;
        }

    }

    /**
     * @param string $dbversion
     * @throws \Exception
     */
    public function hasConfigurationError(string &$dbversion): void
    {
        [$version] = \explode('-', $this->connection->fetchOne('SELECT @@version'));
        $dbversion = $version;

        // The database version is too old
        if (\version_compare($version, '5.1.0', '<')) {
            throw new \Exception('Error: Version < 5.1.0');
        }

        $schema = $this->connection->createSchemaManager();

        $schemaConfig = $schema->createSchemaConfig();
        $options = $schemaConfig->getDefaultTableOptions();

        // Check the collation if the user has configured it
        if (isset($options['collate'])) {
            $row = $this->connection->fetchAssociative("SHOW COLLATION LIKE '" . $options['collate'] . "'");
            // The configured collation is not installed
            if (false === $row) {
                throw new \Exception('Error: configured collation is not installed');
            }
        }

        // Check the engine if the user has configured it
        if (isset($options['engine'])) {
            $engineFound = false;
            $rows = $this->connection->fetchAllAssociative('SHOW ENGINES');

            foreach ($rows as $row) {
                if ($options['engine'] === $row['Engine']) {
                    $engineFound = true;
                    break;
                }
            }

            // The configured engine is not available
            if (!$engineFound) {
                throw new \Exception('Error: configured engine is not available');
            }
        }

        // Check if utf8mb4 can be used if the user has configured it
        if (isset($options['engine'], $options['collate']) && 0 === \strncmp($options['collate'], 'utf8mb4', 7)) {

            if ('innodb' !== \strtolower($options['engine'])) {
                throw new \Exception('Error: utf8mb4 can be used');
            }

            $row = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_large_prefix'");

            // The variable no longer exists as of MySQL 8 and MariaDB 10.3
            if (false === $row || '' === $row['Value']) {
                throw new \Exception('Error: innodb_large_prefix not supported');
            }

            // As there is no reliable way to get the vendor (see #84), we are
            // guessing based on the version number. The check will not be run
            // as of MySQL 8 and MariaDB 10.3, so this should be safe.
            $vok = \version_compare($version, '10', '>=') ? '10.2.2' : '5.7.7';

            // Large prefixes are always enabled as of MySQL 5.7.7 and MariaDB 10.2.2
            if (\version_compare($version, $vok, '>=')) {
                throw new \Exception('Error: invalid version');
            }

            // The innodb_large_prefix option is disabled
            if (!\in_array(\strtolower((string)$row['Value']), ['1', 'on'], true)) {
                throw new \Exception('Error: innodb_large_prefix option is disabled');
            }

            $row = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_file_per_table'");

            // The innodb_file_per_table option is disabled
            if (!\in_array(\strtolower((string)$row['Value']), ['1', 'on'], true)) {
                throw new \Exception('Error: innodb_file_per_table option is disabled');
            }

            $row = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_file_format'");

            // The InnoDB file format is not Barracuda
            if ('' !== $row['Value'] && 'barracuda' !== \strtolower((string)$row['Value'])) {
                throw new \Exception('Error: InnoDB file format is not Barracuda');
            }
        }
    }
}
