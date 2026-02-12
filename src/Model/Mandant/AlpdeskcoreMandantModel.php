<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Mandant;

use Contao\Model;

/**
 * @method static Model|AlpdeskcoreMandantModel|null findById(int $getMandantPid)
 */
class AlpdeskcoreMandantModel extends Model
{
    protected static $strTable = 'tl_alpdeskcore_mandant';
}
