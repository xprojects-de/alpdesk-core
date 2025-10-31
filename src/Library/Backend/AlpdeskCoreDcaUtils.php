<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Backend;

use Alpdesk\AlpdeskCore\Library\PDF\AlpdeskCorePDFCreator;
use Alpdesk\AlpdeskCore\Model\PDF\AlpdeskcorePdfElementsModel;
use Contao\DataContainer;
use Contao\Backend;
use Contao\Input;
use Contao\Controller;
use Alpdesk\AlpdeskCore\Library\Cryption\Cryption;
use Alpdesk\AlpdeskCore\Jwt\JwtToken;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUserProvider;

class AlpdeskCoreDcaUtils extends Backend
{
    /**
     * @param mixed $row
     * @param mixed $label
     * @param mixed $dc
     * @param mixed $args
     * @return array
     */
    public function showSessionValid(mixed $row, mixed $label, mixed $dc, mixed $args): array
    {
        try {
            $validateAndVerify = JwtToken::validateAndVerify($args[1], AlpdeskcoreUserProvider::createJti($args[0]));
        } catch (\Exception) {
            $validateAndVerify = false;
        }

        $color = ($validateAndVerify === true ? 'green' : 'red');

        $args[0] = '<span style="display:inline-block;width:20px;height:20px;margin-right:10px;background-color:' . $color . ';">&nbsp;</span>' . $args[0];
        $args[1] = substr($args[1], 0, 25) . ' ...';

        return $args;
    }

    /**
     * @param mixed $varValue
     * @param mixed $dc
     * @return string
     */
    public function generateFixToken(mixed $varValue, mixed $dc): string
    {
        if ($varValue === null || $varValue === '') {

            $username = 'invalid';

            if ($dc->activeRecord->username !== null && $dc->activeRecord->username !== '') {
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

    /**
     * @param mixed $varValue
     * @param DataContainer $dc
     * @return string
     * @throws \Exception
     */
    public function generateEncryptPassword(mixed $varValue, DataContainer $dc): string
    {
        if ($varValue === '') {
            return $varValue;
        }

        return (new Cryption(true))->safeEncrypt($varValue);
    }

    /**
     * @param mixed $varValue
     * @param DataContainer $dc
     * @return string
     * @throws \Exception
     */
    public function regenerateEncryptPassword(mixed $varValue, DataContainer $dc): string
    {
        if ($varValue === '') {
            return $varValue;
        }

        if ($dc->activeRecord) {
            $crypto = new Cryption(true);
            $varValue = $crypto->safeDecrypt($varValue);
        }

        return $varValue;
    }

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
