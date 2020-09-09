<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Mandant;

use Contao\Model;

class AlpdeskcoreMandantElementsModel extends Model {

  protected static $strTable = 'tl_alpdeskcore_mandant_elements';

  public function findEnabledByPid(int $pid) {
    return self::findBy(array('pid=?', 'disabled!=?'), array($pid, 1));
  }

  public function findEnabledAndVisibleByPid(int $pid) {
    return self::findBy(array('pid=?', 'disabled!=?', 'invisible!=?'), array($pid, 1, 1), array('order' => 'sorting ASC'));
  }

}
