<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Backend;

use Alpdesk\AlpdeskCore\Library\PDF\AlpdeskCorePDFCreator;
use Contao\DataContainer;
use Contao\Backend;
use Contao\Input;
use Contao\Image;
use Contao\StringUtil;
use Contao\Controller;
use Alpdesk\AlpdeskCore\Library\Cryption\Cryption;
use Alpdesk\AlpdeskCore\Jwt\JwtToken;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUserProvider;

class AlpdeskCoreDcaUtils extends Backend
{
    /**
     * @param $row
     * @param $label
     * @param $dc
     * @param $args
     * @return array
     */
    public function showSessionValid($row, $label, $dc, $args): array
    {
        try {
            $validateAndVerify = JwtToken::validateAndVerify($args[1], AlpdeskcoreUserProvider::createJti($args[0]));
        } catch (\Exception $ex) {
            $validateAndVerify = false;
        }

        $color = ($validateAndVerify === true ? 'green' : 'red');

        $args[0] = '<span style="display:inline-block;width:20px;height:20px;margin-right:10px;background-color:' . $color . ';">&nbsp;</span>' . $args[0];
        $args[1] = substr($args[1], 0, 25) . ' ...';

        return $args;
    }

    /**
     * @param $varValue
     * @param $dc
     * @return string
     */
    public function generateFixToken($varValue, $dc): string
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
     * @param $varValue
     * @param DataContainer $dc
     * @return string
     * @throws \Exception
     */
    public function generateEncryptPassword($varValue, DataContainer $dc): string
    {
        if ($varValue === '') {
            return $varValue;
        }

        return (new Cryption(true))->safeEncrypt($varValue);
    }

    /**
     * @param $varValue
     * @param DataContainer $dc
     * @return string
     * @throws \Exception
     */
    public function regenerateEncryptPassword($varValue, DataContainer $dc): string
    {
        if ($varValue === '') {
            return $varValue;
        }

        if ($dc->activeRecord) {
            $cryption = new Cryption(true);
            $varValue = $cryption->safeDecrypt($varValue);
        }

        return $varValue;
    }

    /**
     * @param DataContainer $dc
     * @return void
     */
    public function pdfElementsloadCallback(DataContainer $dc): void
    {
        if (Input::get('act') === 'generatetestpdf') {

            try {
                (new AlpdeskCorePDFCreator())->generateById((int)Input::get('pdfid'), "files/tmp", time() . ".pdf");
            } catch (\Exception $ex) {

            }

            Controller::redirect('contao?do=' . Input::get('do') . '&table=' . Input::get('table') . '&id=' . Input::get('id') . '&rt=' . Input::get('rt'));
        }
    }

    /**
     * @param $arrRow
     * @return string
     */
    public function listPDFElements($arrRow): string
    {
        return $arrRow['name'];
    }

    /**
     * @param $row
     * @param $href
     * @param $label
     * @param $title
     * @param $icon
     * @param $attributes
     * @return string
     */
    public function generatetestpdfLinkCallback($row, $href, $label, $title, $icon, $attributes): string
    {
        return '<a href="' . self::addToUrl($href . '&amp;pdfid=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '" ' . $attributes . '>' . Image::getHtml($icon, $label) . '</a>';
    }
}
