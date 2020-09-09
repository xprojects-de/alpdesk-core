<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Backend;

use Alpdesk\AlpdeskCore\Library\PDF\AlpdeskCorePDFCreator;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use Contao\DataContainer;
use Contao\Backend;
use Contao\Input;
use Contao\Image;
use Alpdesk\AlpdeskCore\Model\Database\AlpdeskcoreDatabasemanagerTablesModel;
use Alpdesk\AlpdeskCore\Library\Cryption\Cryption;
use Alpdesk\AlpdeskCore\Jwt\JwtToken;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUserProvider;

class AlpdeskCoreDcaUtils extends Backend {

  public function showSessionValid($row, $label, $dc, $args): array {
    $validateAndVerify = false;
    try {
      $validateAndVerify = JwtToken::validateAndVerify($args[1], AlpdeskcoreUserProvider::createJti($args[0]));
    } catch (\Exception $ex) {
      $validateAndVerify = false;
    }
    $color = (string) ($validateAndVerify == true ? 'green' : 'red');
    $args[0] = (string) '<span style="display:inline-block;width:20px;height:20px;margin-right:10px;background-color:' . $color . ';">&nbsp;</span>' . $args[0];
    $args[1] = substr($args[1], 0, 25) . ' ...';
    return $args;
  }

  public function addMandantElementType($arrRow): string {
    $key = $arrRow['disabled'] ? 'unpublished' : 'published';
    $icon = (($arrRow['invisible'] | $arrRow['disabled']) ? 'invisible.svg' : 'visible.svg');
    $type = $GLOBALS['TL_LANG']['ADME'][$arrRow['type']] ?: '- INVALID -';
    return '<div class="cte_type ' . $key . '">' . Image::getHtml($icon) . '&nbsp;&nbsp;' . $type . '</div>';
  }

  public function generateFixToken($varValue, $dc) {
    if ($varValue == '') {
      $username = 'invalid';
      if ($dc->activeRecord->username != null && $dc->activeRecord->username != '') {
        $username = $dc->activeRecord->username;
      }
      try {
        $varValue = AlpdeskcoreUserProvider::createToken($username, 0);
      } catch (\Exception $ex) {
        $varValue = $ex->getMessage();
      }
    }
    return $varValue;
  }

  public function generateEncryptPassword($varValue, DataContainer $dc) {
    if ($varValue === '') {
      return $varValue;
    }
    $cryption = new Cryption(true);
    return $cryption->safeEncrypt($varValue);
  }

  public function regenerateEncryptPassword($varValue, DataContainer $dc) {
    if ($varValue === '') {
      return $varValue;
    }
    if ($dc->activeRecord) {
      $cryption = new Cryption(true);
      $varValue = $cryption->safeDecrypt($varValue);
    }
    return $varValue;
  }

  public function getMandantElements() {
    $groups = array();
    if (isset($GLOBALS['TL_ADME']) && count($GLOBALS['TL_ADME'])) {
      foreach ($GLOBALS['TL_ADME'] as $k => $v) {
        $groups[$k] = $GLOBALS['TL_LANG']['ADME'][$k];
      }
    }
    return $groups;
  }

  public function pdfElementsloadCallback(\DataContainer $dc) {
    if (\Input::get('act') == 'generatetestpdf') {
      try {
        $pdf = new AlpdeskCorePDFCreator();
        $tmpFile = $pdf->generateById(intval(\Input::get('id')), "files/tmp", time() . ".pdf");
        $objFile = new \File($tmpFile, true);
        if ($objFile->exists()) {
          $objFile->sendToBrowser(time() . '.pdf');
          $objFile->delete();
        }
      } catch (\Exception $ex) {
        
      }
      $this->redirect('contao?do=' . \Input::get('do') . '&table=' . \Input::get('table') . '&id=' . \Input::get('id') . '&rt=' . \Input::get('rt'));
    }
  }

  public function listPDFElements($arrRow): string {
    return $arrRow['name'];
  }

  public function listDatabasemanagerChildElements($arrRow): string {
    return $arrRow['dbtable'];
  }

  public function databasemanagerelementsOnCopyCallback(int $newId, DataContainer $dc): void {
    $id = Input::get('id');
    $table = Input::get('table');
    if (!$id || !$table) {
      return;
    }
    $oldItems = FieldPaletteModel::findBy(array('pid=?', 'ptable=?'), array($id, $table));
    if ($oldItems === null) {
      return;
    }
    foreach ($oldItems as $item) {
      $itemsArray = $item->row();
      unset($itemsArray['id']);
      $newItem = new FieldPaletteModel();
      $newItem->setRow($itemsArray);
      $newItem->pid = $newId;
      $newItem->save();
    }
  }

  public function databasemanagerelementsOnDeleteCallback(DataContainer $dc, int $undoId): void {
    $id = Input::get('id');
    $table = Input::get('table');
    if (!$id || !$table) {
      return;
    }
    $deleteItems = FieldPaletteModel::findBy(array('pid=?', 'ptable=?'), array($id, $table));
    if ($deleteItems === null) {
      return;
    }
    foreach ($deleteItems as $item) {
      $item->delete();
    }
  }

  public function databasemanagerOnDeleteCallback(DataContainer $dc, int $undoId): void {
    $id = Input::get('id');
    if (!$id) {
      return;
    }
    $elements = AlpdeskcoreDatabasemanagerTablesModel::findBy(array('pid=?'), array($id));
    if ($elements === null) {
      return;
    }
    foreach ($elements as $element) {
      $deleteItems = FieldPaletteModel::findBy(array('pid=?', 'ptable=?'), array($element->id, $dc->childTable[0]));
      if ($deleteItems === null) {
        continue;
      }
      foreach ($deleteItems as $item) {
        $item->delete();
      }
    }
  }

  public function mandantOnDeleteCallback(DataContainer $dc, int $undoId): void {
    $id = Input::get('id');
    $table = Input::get('do');
    if (!$id || !$table) {
      return;
    }
    $table = 'tl_' . $table;
    $deleteItems = FieldPaletteModel::findBy(array('pid=?', 'ptable=?'), array($id, $table));
    if ($deleteItems !== null) {
      foreach ($deleteItems as $item) {
        $item->delete();
      }
    }
  }

}
