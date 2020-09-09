<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Database;

use Contao\Model;
use Alpdesk\AlpdeskCore\Library\Cryption\Cryption;
use Alpdesk\AlpdeskCore\Database\AlpdeskcoreConnectionFactory;
use Doctrine\DBAL\Connection;

class AlpdeskcoreDatabasemanagerModel extends Model {

  protected static $strTable = 'tl_alpdeskcore_databasemanager';

  public function findById(int $id) {
    $result = self::findBy(array('id=?'), array($id));
    if ($result !== null) {
      $decryption = new Cryption(true);
      $result->password = $decryption->safeDecrypt($result->password);
    }
    return $result;
  }

  public function connectionById(int $id): ?Connection {
    $value = null;
    $dbresult = self::findById($id);
    if ($dbresult !== null) {
      $connection = AlpdeskcoreConnectionFactory::create($dbresult->host, intval($dbresult->port), $dbresult->username, $dbresult->password, $dbresult->database);
      return $connection;
    }
    return $value;
  }

}
