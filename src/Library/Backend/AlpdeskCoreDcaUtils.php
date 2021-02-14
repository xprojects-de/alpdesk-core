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

  public function pdfElementsloadCallback(DataContainer $dc) {
    if (Input::get('act') == 'generatetestpdf') {
      try {
        (new AlpdeskCorePDFCreator())->generateById(intval(Input::get('pdfid')), "files/tmp", time() . ".pdf");
      } catch (\Exception $ex) {
        
      }
      Controller::redirect('contao?do=' . Input::get('do') . '&table=' . Input::get('table') . '&id=' . Input::get('id') . '&rt=' . Input::get('rt'));
    }
  }

  public function listPDFElements($arrRow): string {
    return $arrRow['name'];
  }

  public function generatetestpdfLinkCallback($row, $href, $label, $title, $icon, $attributes) {

    return '<a href="' . $this->addToUrl($href . '&amp;pdfid=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a>';
  }

}
