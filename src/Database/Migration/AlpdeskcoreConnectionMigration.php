<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Database\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Contao\Model\Collection;
use Contao\StringUtil;
use Alpdesk\AlpdeskCore\Model\Database\AlpdeskcoreDatabasemanagerTablesModel;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;

class AlpdeskcoreConnectionMigration {

  private Connection $connection;
  private ?Collection $tableInfo;
  private string $prefix;

  public function __construct(Connection $connection, ?Collection $tableInfo, string $prefix) {
    $this->connection = $connection;
    $this->tableInfo = $tableInfo;
    $this->prefix = $prefix;
  }

  public function setTableInfo(?Collection $tableInfo): void {
    $this->tableInfo = $tableInfo;
  }

  private function getFilterClosure() {
    // Only Show prefix-Tables
    return function (string $assetName): bool {
      return 0 === strncmp($assetName, $this->prefix, intval(strlen($this->prefix)));
    };
  }

  public function executeMigrations($commands): void {
    foreach ($commands as $command) {
      $this->connection->query($command);
    }
  }

  private function setDefault(Column $column): array {
    $value = array(
        'adddefault' => '1',
        'default' => ($column->getDefault() !== null ? $column->getDefault() : 'NULL')
    );
    return $value;
  }

  private function setType(string $type, int $length): string {
    if ($type == 'integer') {
      $type = 'int';
    } else if ($type == 'string' && $length > 0) {
      if ($length == 1) {
        $type = 'char';
      } else {
        $type = 'varchar';
      }
    } else if ($type == 'boolean') {
      $type = 'tinyint';
    } else if ($type == 'binary_string') {
      $type = 'binary';
    }
    return $type;
  }

  private function setLength(string $type, int $length): int {
    if ($type == 'tinyint') {
      $length = 1;
    }
    return $length;
  }

  public function importMigrations(int $pid): void {
    $config = $this->connection->getConfiguration();
    $previousFilter = $config->getSchemaAssetsFilter();
    $config->setSchemaAssetsFilter($this->getFilterClosure());
    $fromSchema = $this->connection->getSchemaManager()->createSchema();
    $config->setSchemaAssetsFilter($previousFilter);
    if ($fromSchema !== null) {
      foreach ($fromSchema->getTables() as $table) {
        if ($table instanceof Table) {
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
          $columns = $table->getColumns();
          if ($columns !== null && \is_array($columns) && count($columns) > 0) {
            $tableModel = new AlpdeskcoreDatabasemanagerTablesModel();
            $tableModel->pid = $pid;
            $tableModel->dbtable = $table->getName();
            $tableModel->dbindex = \serialize($indexes);
            $tableModel->save();
            foreach ($columns as $column) {
              $length = ($column->getLength() !== null ? intval($column->getLength()) : 0);
              $type = $this->setType($column->getType()->getName(), $length);
              $length = $this->setLength($type, $length);
              if ($column instanceof Column) {
                $default = $this->setDefault($column);
                $newColumn = new FieldPaletteModel();
                $newColumn->pid = $tableModel->id;
                $newColumn->ptable = $tableModel->getTableName();
                $newColumn->pfield = 'dbfields';
                $newColumn->databasemanager_name = $column->getName();
                $newColumn->databasemanager_type = $type;
                $newColumn->databasemanager_length = $length;
                $newColumn->databasemanager_unsigned = ($column->getUnsigned() === true ? '1' : '');
                $newColumn->databasemanager_null = ($column->getNotnull() === true ? 'NOT NULL' : 'NULL');
                $newColumn->databasemanager_autoincrement = ($column->getAutoincrement() === true ? '1' : '');
                $newColumn->databasemanager_default = $default['default'];
                $newColumn->databasemanager_adddefault = $default['adddefault'];
                $newColumn->databasemanager_primary = (\in_array($column->getName(), $primaryKey) ? '1' : '');
                $newColumn->published = '1';
                $newColumn->save();
              }
            }
          }
        }
      }
      $fieldPalettesModel = new AlpdeskcoreDatabasemanagerTablesModel();
      $this->setTableInfo($fieldPalettesModel->findPublishedElementsByPid($pid));
    }
  }

  public function showMigrations(): array {
    $config = $this->connection->getConfiguration();
    $previousFilter = $config->getSchemaAssetsFilter();
    $config->setSchemaAssetsFilter($this->getFilterClosure());
    $fromSchema = $this->connection->getSchemaManager()->createSchema();
    $config->setSchemaAssetsFilter($previousFilter);
    $diff = $fromSchema->getMigrateToSql($this->parseSql(), $this->connection->getDatabasePlatform());
    return $diff;
  }

  private function parseSql(): Schema {
    $schema = new Schema();
    if ($this->tableInfo !== null) {
      foreach ($this->tableInfo as $currentTable) {
        $table = $schema->createTable($currentTable->dbtable);
        if (\is_array($currentTable->tableElements)) {
          $primary = array();
          foreach ($currentTable->tableElements as $fields) {
            if (\is_array($fields)) {
              $this->parseFields($table, $fields);
              if (intval($fields['databasemanager_primary']) == 1) {
                array_push($primary, $fields['databasemanager_name']);
              }
            }
          }
          if (count($primary) > 0) {
            $table->setPrimaryKey($primary);
          }
          $tableIndex = (array) StringUtil::deserialize($currentTable->dbindex);
          if (count($tableIndex) > 0) {
            foreach ($tableIndex as $tIndex) {
              if ($tIndex['indexfields'] != '') {
                $indexFields = explode(',', $tIndex['indexfields']);
                $indexFields = str_replace(' ', '', $indexFields);
                $indexName = $tIndex['indexname'];
                if ($indexName == '') {
                  $indexName = implode('_', $indexFields);
                }
                if (intval($tIndex['indextype']) == 1) {
                  $table->addUniqueIndex($indexFields, $indexName);
                } else {
                  $table->addIndex($indexFields, $indexName);
                }
              }
            }
          }
        }
      }
    }
    return $schema;
  }

  private function parseFields(Table $table, array $fields): void {
    $dbType = $fields['databasemanager_type'];
    $type = $fields['databasemanager_type'];
    $length = null;
    if ($fields['databasemanager_length'] != '') {
      $length = (int) $fields['databasemanager_length'];
      $dbType = $type . '(' . $length . ')';
    }
    $fixed = false;
    $scale = null;
    $precision = null;
    $collation = null;
    $unsigned = false;
    if (intval($fields['databasemanager_unsigned']) == 1 && \in_array(strtolower($type), array('tinyint', 'smallint', 'mediumint', 'int', 'bigint'))) {
      $unsigned = true;
    }
    $autoincrement = false;
    if (intval($fields['databasemanager_autoincrement']) == 1) {
      $autoincrement = true;
    }
    $default = $fields['databasemanager_default'];
    // @TODO Maybe if 'NULL'create readl NULL
    if ($autoincrement == true || $default == 'NULL') {
      $default = null;
    }

    $this->setLengthAndPrecisionByType($type, $dbType, $length, $scale, $precision, $fixed);
    /*
      // Dump for ErrorChecking
      if ($type == 'binary_string') {
      dump($table);
      dump($dbType);
      dump($length);
      dump($fields['databasemanager_name']);
      dump($fields);
      dump($this->connection->getDatabasePlatform());
      $type = 'binary';
      die;
      }
     */
    $type = $this->connection->getDatabasePlatform()->getDoctrineTypeMapping($type);
    if (0 === $length) {
      $length = null;
    }
    if (strtolower($type) == 'binary') {
      $collation = $this->getBinaryCollation($table);
    }
    $options = [
        'length' => $length,
        'unsigned' => $unsigned,
        'fixed' => $fixed,
        'default' => $default,
        'notnull' => (false !== stripos($fields['databasemanager_null'], 'NOT NULL')),
        'scale' => null,
        'precision' => null,
        'autoincrement' => $autoincrement,
        'comment' => null,
    ];
    if (null !== $scale && null !== $precision) {
      $options['scale'] = $scale;
      $options['precision'] = $precision;
    }
    if (null !== $collation) {
      $options['platformOptions'] = ['collation' => $collation];
    }
    $table->addColumn($fields['databasemanager_name'], $type, $options);
  }

  private function setLengthAndPrecisionByType(string $type, string $dbType, ?int &$length, ?int &$scale, ?int &$precision, bool &$fixed): void {
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
        if (preg_match('/[a-z]+\((\d+),(\d+)\)/i', $dbType, $match)) {
          $length = null;
          [, $precision, $scale] = $match;
        }
        break;

      case 'tinytext':
        $length = MySqlPlatform::LENGTH_LIMIT_TINYTEXT;
        break;

      case 'text':
        $length = MySqlPlatform::LENGTH_LIMIT_TEXT;
        break;

      case 'mediumtext':
        $length = MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT;
        break;

      case 'tinyblob':
        $length = MySqlPlatform::LENGTH_LIMIT_TINYBLOB;
        break;

      case 'blob':
        $length = MySqlPlatform::LENGTH_LIMIT_BLOB;
        break;

      case 'mediumblob':
        $length = MySqlPlatform::LENGTH_LIMIT_MEDIUMBLOB;
        break;

      case 'tinyint':
      case 'smallint':
      case 'mediumint':
      case 'int':
      case 'integer':
      case 'bigint':
      case 'year':
        $length = null;
    }
  }

  private function getBinaryCollation(Table $table): ?string {
    if (!$table->hasOption('charset')) {
      return null;
    }
    return $table->getOption('charset') . '_bin';
  }

}
