<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Database\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;

class AlpdeskcoreConnectionMigrationCheck {

  private Connection $connection;

  public function setConnection(Connection $connection): void {
    $this->connection = $connection;
  }

  public function canConnectToDatabase(?string $name): bool {
    try {
      $this->connection->connect();
      $this->connection->query('SHOW TABLES');
      return true;
    } catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
    if (null === $name || null === $this->connection) {
      return false;
    }
    try {
      $this->connection->connect();
    } catch (\Exception $e) {
      return false;
    }
    $quotedName = $this->connection->quoteIdentifier($name);
    try {
      $this->connection->query('use ' . $quotedName);
    } catch (DBALException $e) {
      return false;
    }
    return true;
  }

  public function hasConfigurationError() {
    $row = $this->connection->query('SELECT @@version as Version')->fetch(\PDO::FETCH_OBJ);
    [$version] = explode('-', $row->Version);
    // The database version is too old
    if (version_compare($version, '5.1.0', '<')) {
      throw new \Exception('Error: Version < 5.1.0');
    }
    $options = $this->connection->getParams()['defaultTableOptions'];
    // Check the collation if the user has configured it
    if (isset($options['collate'])) {
      $statement = $this->connection->query("SHOW COLLATION LIKE '" . $options['collate'] . "'");
      // The configured collation is not installed
      if (false === ($row = $statement->fetch(\PDO::FETCH_OBJ))) {
        throw new \Exception('Error: configured collation is not installed');
      }
    }
    // Check the engine if the user has configured it
    if (isset($options['engine'])) {
      $engineFound = false;
      $statement = $this->connection->query('SHOW ENGINES');
      while (false !== ($row = $statement->fetch(\PDO::FETCH_OBJ))) {
        if ($options['engine'] === $row->Engine) {
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
    if (isset($options['engine'], $options['collate']) && 0 === strncmp($options['collate'], 'utf8mb4', 7)) {
      if ('innodb' !== strtolower($options['engine'])) {
        throw new \Exception('Error: utf8mb4 can be used');
      }
      $row = $this->connection->query("SHOW VARIABLES LIKE 'innodb_large_prefix'")->fetch(\PDO::FETCH_OBJ);
      // The variable no longer exists as of MySQL 8 and MariaDB 10.3
      if (false === $row || '' === $row->Value) {
        throw new \Exception('Error: innodb_large_prefix not supported');
      }
      // As there is no reliable way to get the vendor (see #84), we are
      // guessing based on the version number. The check will not be run
      // as of MySQL 8 and MariaDB 10.3, so this should be safe.
      $vok = version_compare($version, '10', '>=') ? '10.2.2' : '5.7.7';
      // Large prefixes are always enabled as of MySQL 5.7.7 and MariaDB 10.2.2
      if (version_compare($version, $vok, '>=')) {
        throw new \Exception('Error: invalid version');
      }
      // The innodb_large_prefix option is disabled
      if (!\in_array(strtolower((string) $row->Value), ['1', 'on'], true)) {
        throw new \Exception('Error: innodb_large_prefix option is disabled');
      }
      $row = $this->connection->query("SHOW VARIABLES LIKE 'innodb_file_per_table'")->fetch(\PDO::FETCH_OBJ);
      // The innodb_file_per_table option is disabled
      if (!\in_array(strtolower((string) $row->Value), ['1', 'on'], true)) {
        throw new \Exception('Error: innodb_file_per_table option is disabled');
      }
      $row = $this->connection->query("SHOW VARIABLES LIKE 'innodb_file_format'")->fetch(\PDO::FETCH_OBJ);
      // The InnoDB file format is not Barracuda
      if ('' !== $row->Value && 'barracuda' !== strtolower((string) $row->Value)) {
        throw new \Exception('Error: InnoDB file format is not Barracuda');
      }
    }
  }

}
