<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Mandant;

use Contao\Model;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreModelException;

class AlpdeskcoreMandantModel extends Model {

  protected static $strTable = 'tl_alpdeskcore_mandant';

  public static function findByAuthUsername($username) {
    $fieldPalettesModel = new FieldPaletteModel();
    $r = $fieldPalettesModel->findPublishedBy(array('username=?', 'pfield=?', 'ptable=?'), array($username, 'auth', self::$strTable));
    if ($r !== null) {
      return $r;
    } else {
      throw new AlpdeskCoreModelException("error auth - invalid username");
    }
  }

  public static function findByAuthUsernameAndFixtoken($username, $fixtoken) {
    $fieldPalettesModel = new FieldPaletteModel();
    $r = $fieldPalettesModel->findPublishedBy(array('username=?', 'pfield=?', 'ptable=?', 'fixtoken=?'), array($username, 'auth', self::$strTable, $fixtoken));
    if ($r !== null) {
      return $r;
    } else {
      throw new AlpdeskCoreModelException("error auth - invalid username or fixtoken");
    }
  }

}
