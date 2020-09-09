<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Database;

use Contao\Model;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;

class AlpdeskcoreDatabasemanagerTablesModel extends Model {

  protected static $strTable = 'tl_alpdeskcore_databasemanager_tables';

  public function findPublishedElementsByPid(int $pid) {
    $value = $this->findBy(array('pid=?'), array($pid));
    if ($value !== null) {
      $fieldPalettesModel = new FieldPaletteModel();
      foreach ($value as $current) {
        $elements = $fieldPalettesModel->findPublishedByPidAndTableAndField(intval($current->id), static::$strTable, 'dbfields');
        if ($elements !== null) {
          $tmp = array();
          foreach ($elements as $element) {
            \array_push($tmp, $element->row());
          }
          $current->tableElements = $tmp;
        }
      }
    }
    return $value;
  }

  public function getTableName(): string {
    return 'tl_alpdeskcore_databasemanager_tables';
  }

}
