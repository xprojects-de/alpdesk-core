<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;

class AlpdeskcoreConnectionFactory {

  private Connection $connection;

  public static function create(string $host, int $port, string $username, string $password, string $database): ?Connection {
    $params = [
        'driver' => 'pdo_mysql',
        'host' => $host,
        'port' => $port,
        'user' => $username,
        'password' => $password,
        'dbname' => $database
    ];
    try {
      return DriverManager::getConnection($params);
    } catch (DBALException $e) {
      
    }
    return null;
  }

  public function getConnection(): Connection {
    return $this->connection;
  }

  public function setConnection(Connection $connection): void {
    $this->connection = $connection;
  }

  public static function listTables(Connection $connection, ?string $name): array {

    if (null === $name || null === $connection) {
      throw new \Exception($GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['invalid_parameters']);
    }

    try {
      $connection->connect();
      $tables = $connection->getSchemaManager()->createSchema()->getTables();
      $structure = array();
      foreach ($tables as $table) {
        if ($table instanceof Table) {

          $options = "";
          $tableOptions = $table->getOptions();
          if ($tableOptions !== null && \is_array($tableOptions) && count($tableOptions) > 0) {
            if (\array_key_exists('engine', $tableOptions)) {
              $options .= 'engine: ' . $tableOptions['engine'];
            }
            if (\array_key_exists('collation', $tableOptions)) {
              $options .= ' | collation: ' . $tableOptions['collation'];
            }
            if (\array_key_exists('autoincrement', $tableOptions)) {
              $options .= ' | autoincrement: ' . $tableOptions['autoincrement'];
            }
          }

          $primaryKey = array();
          foreach ($table->getPrimaryKey()->getColumns() as $column) {
            array_push($primaryKey, $column);
          }
          $indexes = array();
          foreach ($table->getIndexes() as $indexEntry) {
            if ('PRIMARY' !== $indexEntry->getName()) {
              $indexInfo = array(
                  'indextype' => ($indexEntry->isUnique() ? 1 : 0),
                  'indexfields' => '',
                  'indexname' => $indexEntry->getName()
              );
              $tmpIndexFields = array();
              foreach ($indexEntry->getColumns() as $column) {
                array_push($tmpIndexFields, $column);
              }
              $indexInfo['indexfields'] = implode(',', $tmpIndexFields);
              array_push($indexes, $indexInfo);
            }
          }

          $structure[$table->getName()] = array(
              'options' => $options,
              'primary' => implode(', ', $primaryKey),
              'indexes' => implode(', ', $indexes)
          );

          $columns = $table->getColumns();
          if ($columns !== null && \is_array($columns) && count($columns) > 0) {
            foreach ($columns as $column) {
              if ($column instanceof Column) {
                $type = $column->getType();
                $autoincrement = $column->getAutoincrement();
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
                if ($length !== null && $length != "") {
                  $output .= ' | LENGTH ' . $length;
                }
                $platformOptions = $column->getPlatformOptions();
                if ($platformOptions !== null && is_array($platformOptions) && count($platformOptions) > 0) {
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

}
