<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Database;

use Contao\Model;
use Alpdesk\AlpdeskCore\Library\Cryption\Cryption;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;

class AlpdeskcoreDatabasemanagerModel extends Model {

  protected static $strTable = 'tl_alpdeskcore_databasemanager';
  private static $connectionsTable = [];

  private static function create(int $id, string $host, int $port, string $username, string $password, string $database): ?Connection {

    if (\array_key_exists($id, self::$connectionsTable)) {
      if (self::$connectionsTable[$id] instanceof Connection) {
        if (self::$connectionsTable[$id] !== null) {
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
        'dbname' => $database
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

  public static function destroy(int $id) {
    if (\array_key_exists($id, self::$connectionsTable)) {
      if (self::$connectionsTable[$id] instanceof Connection) {
        if (self::$connectionsTable[$id] !== null) {
          self::$connectionsTable[$id]->close();
          self::$connectionsTable[$id] = null;
        }
      }
    }
  }

  public static function listTables(int $id, ?string $name): array {

    if (null === $name || !\array_key_exists($id, self::$connectionsTable)) {
      throw new \Exception($GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['invalid_parameters']);
    }

    if (self::$connectionsTable[$id] == null) {
      throw new \Exception($GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['invalid_parameters']);
    }

    try {

      $tables = self::$connectionsTable[$id]->getSchemaManager()->createSchema()->getTables();

      $structure = array();
      foreach ($tables as $table) {
        if ($table instanceof Table) {

          $options = "";
          $tableOptions = $table->getOptions();
          if ($tableOptions !== null && \is_array($tableOptions) && \count($tableOptions) > 0) {
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
              array_push($primaryKey, $column);
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
                \array_push($tmpIndexFields, $column);
              }
              $indexInfo['indexfields'] = \implode(',', $tmpIndexFields);
              \array_push($indexes, $indexInfo);
            }
          }

          $indiciesStringArray = array();
          if (\is_array($indexes) && \count($indexes) > 0) {
            foreach ($indexes as $ind) {
              \array_push($indiciesStringArray, 'Name: ' . $ind['indexname'] . ', Unique: ' . ($ind['indexunique'] === true ? 'true' : 'false' ) . ', Fields: ' . $ind['indexfields']);
            }
          }
          $structure[$table->getName()] = array(
              'options' => $options,
              'primary' => \implode('<br>', $primaryKey),
              'indexes' => \implode('<br>', $indiciesStringArray)
          );

          $columns = $table->getColumns();
          if ($columns !== null && \is_array($columns) && \count($columns) > 0) {
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
                if ($platformOptions !== null && \is_array($platformOptions) && \count($platformOptions) > 0) {
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

  public static function connectionById(int $id): ?Connection {
    $result = self::findByPk($id);
    if ($result !== null) {
      $decryption = new Cryption(true);
      $result->password = $decryption->safeDecrypt($result->password);
      return self::create($id, $result->host, \intval($result->port), $result->username, $result->password, $result->database);
    }
    return null;
  }

}
