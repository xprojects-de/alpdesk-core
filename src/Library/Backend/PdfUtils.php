<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Backend;

use Alpdesk\AlpdeskCore\Library\PDF\AlpdeskCorePDFCreator;
use Alpdesk\AlpdeskCore\Model\PDF\AlpdeskcorePdfElementsModel;
use Contao\Controller;
use Contao\Input;

class PdfUtils
{
    /**
     * @return void
     */
    public function generatePdfPreview(): void
    {
        if (Input::get('key') === 'generate_pdf_preview') {

            try {
                (new AlpdeskCorePDFCreator())->generateById((int)Input::get('id'), "files/tmp", time() . ".pdf");
            } catch (\Exception) {
            }
        }

        $elementsModel = AlpdeskcorePdfElementsModel::findById((int)Input::get('id'));
        $pid = $elementsModel?->pid;
        if ($pid !== null) {
            Controller::redirect('contao?do=' . Input::get('do') . '&table=' . Input::get('table') . '&id=' . (int)$pid);
        } else {
            Controller::redirect('contao?do=alpdeskcore_pdf');
        }

    }

}