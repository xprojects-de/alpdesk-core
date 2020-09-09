<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\PDF;

use Alpdesk\AlpdeskCore\Model\PDF\AlpdeskcorePdfElementsModel;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCorePDFException;
use Contao\Controller;

class AlpdeskCorePDFCreator extends \TCPDF {

  private array $search_custom = array();
  private array $replace_custom = array();
  private array $footersesstingsarray = array(
      'valid' => false,
      'text' => 'Hallo Footer',
      'font' => 'helvetica',
      'fontstyle' => 'I',
      'fontsize' => 10,
      'bottomoffset' => 10,
      'alignment' => 'C',
      'width' => 0,
      'height' => 0
  );
  private array $headersesstingsarray = array(
      'valid' => false,
      'text' => '',
      'font' => 'helvetica',
      'fontstyle' => 'B',
      'fontsize' => 10,
      'alignment' => 'C',
      'width' => 0,
      'height' => 0
  );

  public function __construct() {
    parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true);
  }

  public function setFootersesstingsarray(array $footersesstingsarray): void {
    $this->footersesstingsarray = $footersesstingsarray;
  }

  public function setHeadersesstingsarray(array $headersesstingsarray): void {
    $this->headersesstingsarray = $headersesstingsarray;
  }

  public function setHeaderDataItem($key, $value) {
    $this->headersesstingsarray[$key] = $value;
  }

  public function setFooterDataItem($key, $value) {
    $this->footersesstingsarray[$key] = $value;
  }

  public function getHeaderDataItem($key) {
    return $this->headersesstingsarray[$key];
  }

  public function getFooterDataItem($key) {
    return $this->footersesstingsarray[$key];
  }

  // Preserve for TCPDF
  public function Header() {
    if ($this->headersesstingsarray['valid'] == true) {
      $this->SetFont($this->headersesstingsarray['font'], $this->headersesstingsarray['fontstyle'], $this->headersesstingsarray['fontsize']);
      $this->writeHTMLCell($this->headersesstingsarray['width'], $this->headersesstingsarray['height'], '', '', $this->headersesstingsarray['text'], 0, 0, false, $this->headersesstingsarray['alignment'], true);
    }
  }

  // Preserve for TCPDF
  public function Footer() {
    if ($this->footersesstingsarray['valid'] == true) {
      $this->SetY(-(intval($this->footersesstingsarray['bottomoffset'])));
      $this->SetFont($this->footersesstingsarray['font'], $this->footersesstingsarray['fontstyle'], $this->footersesstingsarray['fontsize']);
      //$w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true
      $this->writeHTMLCell($this->footersesstingsarray['width'], $this->footersesstingsarray['height'], '', '', $this->footersesstingsarray['text'], 0, 0, false, $this->footersesstingsarray['alignment'], true);
      //$this->Cell($this->footersesstingsarray['width'], $this->footersesstingsarray['height'], $this->footersesstingsarray['text'], 0, false, $this->footersesstingsarray['alignment']);
    }
  }

  public function setReplaceData(array $search, array $replace): void {
    $this->replace_custom = $replace;
    $this->search_custom = $search;
  }

  public function generateById(int $id, string $path, string $pdfname): string {
    $pdfData = AlpdeskcorePdfElementsModel::findById($id);
    if ($pdfData === null) {
      throw new AlpdeskCorePDFException("id for PDF not found");
    }
    $font = \StringUtil::deserialize($pdfData->font);
    $settingsarray = array(
        'font_family' => ($font[0] != "" ? $font[0] : 'helvetica'),
        'font_size' => ($font[1] != "" ? $font[1] : '12'),
        'font_style' => ($font[2] != "" ? $font[2] : ''),
        'pdfauthor' => $pdfData->pdfauthor,
        'pdftitel' => $pdfData->name
    );
    $footerglobalsize = \StringUtil::deserialize($pdfData->footer_globalsize);
    $footerglobalfont = \StringUtil::deserialize($pdfData->footer_globalfont);
    $this->footersesstingsarray = array(
        'valid' => ($pdfData->footer_text != '' ? true : false),
        'text' => $pdfData->footer_text,
        'font' => ($footerglobalfont[0] != "" ? $footerglobalfont[0] : 'helvetica'),
        'fontstyle' => ($footerglobalfont[2] != "" ? $footerglobalfont[2] : ''),
        'fontsize' => ($footerglobalfont[1] != "" ? intval($footerglobalfont[1]) : '10'),
        'bottomoffset' => 10,
        'alignment' => ($footerglobalfont[3] != "" ? $footerglobalfont[3] : ''),
        'width' => intval($footerglobalsize[0]),
        'height' => intval($footerglobalsize[1])
    );
    $headerglobalsize = \StringUtil::deserialize($pdfData->header_globalsize);
    $headerglobalfont = \StringUtil::deserialize($pdfData->header_globalfont);
    $this->headersesstingsarray = array(
        'valid' => ($pdfData->header_text != '' ? true : false),
        'text' => $pdfData->header_text,
        'width' => intval($headerglobalsize[0]),
        'height' => intval($headerglobalsize[1]),
        'font' => ($headerglobalfont[0] != "" ? $headerglobalfont[0] : 'helvetica'),
        'fontstyle' => ($headerglobalfont[2] != "" ? $headerglobalfont[2] : 'B'),
        'fontsize' => ($headerglobalfont[1] != "" ? intval($headerglobalfont[1]) : '10'),
        'alignment' => ($headerglobalfont[3] != "" ? $headerglobalfont[3] : '')
    );
    $objFile = new \File($path . "/" . $pdfname, true);
    if ($objFile->exists()) {
      $objFile->delete();
    }
    return $this->generate($pdfData->html, $pdfname, $path, $settingsarray);
  }

  public function generate($text, $filename, $path, $settingsarray): string {
    ob_start();
    $l['a_meta_dir'] = 'ltr';
    $l['a_meta_charset'] = $GLOBALS['TL_CONFIG']['characterSet'];
    $l['a_meta_language'] = $GLOBALS['TL_LANGUAGE'];
    $l['w_page'] = "page";
    $this->SetCreator(PDF_CREATOR);
    $this->SetAuthor($settingsarray['pdfauthor']);
    $this->SetTitle($settingsarray['pdftitel']);
    $this->SetSubject("");
    $this->SetKeywords("");
    $this->setFontSubsetting(false);
    foreach ($this->headersesstingsarray as $key => $value) {
      if ($key == 'text') {
        $value = str_replace($this->search_custom, $this->replace_custom, Controller::replaceInsertTags($value, false));
      }
      $this->setHeaderDataItem($key, $value);
    }
    foreach ($this->footersesstingsarray as $key => $value) {
      if ($key == 'text') {
        $value = str_replace($this->search_custom, $this->replace_custom, Controller::replaceInsertTags($value, false));
      }
      $this->setFooterDataItem($key, $value);
    }
    $this->setPrintHeader($this->getHeaderDataItem('valid'));
    $this->setPrintFooter($this->getFooterDataItem('valid'));
    $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP + ( $this->getHeaderDataItem('valid') == true ? intval($this->getHeaderDataItem('height')) : 0), PDF_MARGIN_RIGHT);
    $this->SetHeaderMargin(PDF_MARGIN_HEADER);
    $this->SetFooterMargin(PDF_MARGIN_FOOTER);
    $this->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM + 10);
    $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $this->setLanguageArray($l);
    $this->SetFont($settingsarray['font_family'], $settingsarray['font_style'], $settingsarray['font_size']);
    $this->AddPage();
    $html = str_replace($this->search_custom, $this->replace_custom, Controller::replaceInsertTags($text, false));
    $this->writeHTML($html, true, false, true, false, '');
    $this->lastPage();
    $xdir = TL_ROOT . "/" . $path;
    if (!is_dir($xdir)) {
      mkdir($xdir, 0777);
    }
    $this->Output($xdir . "/" . $filename, 'F');
    ob_end_clean();
    return $path . "/" . $filename;
  }

}
