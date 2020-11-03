<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;

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
      $sm = $connection->getSchemaManager();
      $structure = array();
      $tables = $sm->listTableNames();
      foreach ($tables as $table) {
        $columns = $sm->listTableColumns($table);
        if (is_array($columns) && count($columns) > 0) {
          $structure[$table] = array();
          foreach ($columns as $column) {
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
            if ($default !== null && $default != "") {
              $output .= ' | DEFAULT ' . $default;
            }
            $length = $column->getLength();
            if ($length !== null && $length != "") {
              $output .= ' | LENGTH ' . $length;
            }
            $structure[$table][$column->getName()] = $output;
          }
        }
      }
      //dump($structure);
      //die;
      return $structure;
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
  }

}
