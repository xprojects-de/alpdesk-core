<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\PDF;

use Contao\Model;

/**
 * @method static Model|AlpdeskcorePdfElementsModel|null findById(int $id)
 */
class AlpdeskcorePdfElementsModel extends Model
{
    protected static $strTable = 'tl_alpdeskcore_pdf_elements';
}
