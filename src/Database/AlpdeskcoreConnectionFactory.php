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

}
