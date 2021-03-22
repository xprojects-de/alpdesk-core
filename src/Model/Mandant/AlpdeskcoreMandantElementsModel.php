<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Mandant;

use Contao\Model;

class AlpdeskcoreMandantElementsModel extends Model
{
    protected static $strTable = 'tl_alpdeskcore_mandant_elements';

    public static function findEnabledByPid(int $pid)
    {
        return self::findBy(['tl_alpdeskcore_mandant_elements.pid=?', 'tl_alpdeskcore_mandant_elements.disabled!=?'], [$pid, 1]);
    }

    public static function findEnabledAndVisibleByPid(int $pid)
    {
        return self::findBy(['tl_alpdeskcore_mandant_elements.pid=?', 'tl_alpdeskcore_mandant_elements.disabled!=?', 'tl_alpdeskcore_mandant_elements.invisible!=?'], [$pid, 1, 1], ['order' => 'sorting ASC']);
    }
}
