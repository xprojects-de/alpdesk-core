<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\PDF;

use Alpdesk\AlpdeskCore\Model\PDF\AlpdeskcorePdfElementsModel;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCorePDFException;
use Alpdesk\AlpdeskCore\Utils\Utils;
use Contao\Config;
use Contao\StringUtil;
use Contao\File;
use Contao\Dbafs;
use Contao\System;
use Symfony\Component\Filesystem\Filesystem;

class AlpdeskCorePDFCreator extends \TCPDF
{
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

    private array $pageMargins = [
        'left' => null,
        'top' => null,
        'right' => null,
        'footerMargin' => null,
        'headerMargin' => null,
        'autobreakMargin' => null
    ];

    public function __construct()
    {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT);
    }

    public function setFootersesstingsarray(array $footersesstingsarray): void
    {
        $this->footersesstingsarray = $footersesstingsarray;
    }

    public function setHeadersesstingsarray(array $headersesstingsarray): void
    {
        $this->headersesstingsarray = $headersesstingsarray;
    }

    public function setHeaderDataItem(mixed $key, mixed $value): void
    {
        $this->headersesstingsarray[$key] = $value;
    }

    public function setFooterDataItem(mixed $key, mixed $value): void
    {
        $this->footersesstingsarray[$key] = $value;
    }

    public function getHeaderDataItem(mixed $key): mixed
    {
        return $this->headersesstingsarray[$key];
    }

    public function getFooterDataItem(mixed $key): mixed
    {
        return $this->footersesstingsarray[$key];
    }

    // Preserve for TCPDF
    public function Header(): void
    {
        if ($this->headersesstingsarray['valid'] === true) {

            $align = ($this->headersesstingsarray['alignment'] ?? '');
            if ($align === '') {
                $align = 'C';
            }

            $this->SetFont($this->headersesstingsarray['font'], $this->headersesstingsarray['fontstyle'], $this->headersesstingsarray['fontsize']);
            $this->writeHTMLCell(
                $this->headersesstingsarray['width'],
                $this->headersesstingsarray['height'],
                null,
                null,
                $this->headersesstingsarray['text'],
                0,
                0,
                false,
                true,
                $align
            );

        }
    }

    // Preserve for TCPDF
    public function Footer(): void
    {
        if ($this->footersesstingsarray['valid'] === true) {

            $align = ($this->footersesstingsarray['alignment'] ?? '');
            if ($align === '') {
                $align = 'C';
            }

            $this->SetY(-((int)$this->footersesstingsarray['bottomoffset']));
            $this->SetFont($this->footersesstingsarray['font'], $this->footersesstingsarray['fontstyle'], $this->footersesstingsarray['fontsize']);

            $this->writeHTMLCell(
                $this->footersesstingsarray['width'],
                $this->footersesstingsarray['height'],
                null,
                null,
                $this->footersesstingsarray['text'],
                0,
                0,
                false,
                true,
                $align
            );
            //$this->Cell($this->footersesstingsarray['width'], $this->footersesstingsarray['height'], $this->footersesstingsarray['text'], 0, false, $this->footersesstingsarray['alignment']);

        }
    }

    public function setReplaceData(array $search, array $replace): void
    {
        $this->replace_custom = $replace;
        $this->search_custom = $search;
    }

    /**
     * @param int $id
     * @param string $path
     * @param string $pdfname
     * @return string
     * @throws \Exception
     */
    public function generateById(int $id, string $path, string $pdfname): string
    {
        $pdfData = AlpdeskcorePdfElementsModel::findById($id);

        if ($pdfData === null) {
            throw new AlpdeskCorePDFException('id for PDF not found');
        }

        $font = StringUtil::deserialize($pdfData->font);

        $settingsarray = array(
            'font_family' => ($font[0] !== '' ? $font[0] : 'helvetica'),
            'font_size' => ($font[1] !== '' ? $font[1] : '12'),
            'font_style' => ($font[2]),
            'pdfauthor' => $pdfData->pdfauthor,
            'pdftitel' => $pdfData->name
        );

        if ($pdfData->margins !== null && $pdfData->margins !== '') {

            $margins = StringUtil::deserialize($pdfData->margins);
            if (\is_array($margins) && \count($margins) === 3) {

                $marginLeft = $margins[0];
                if ($marginLeft !== null && $marginLeft !== '') {
                    $this->pageMargins['left'] = (float)$marginLeft;
                }

                $marginTop = $margins[1];
                if ($marginTop !== null && $marginTop !== '') {
                    $this->pageMargins['top'] = (float)$marginTop;
                }

                $marginRight = $margins[2];
                if ($marginRight !== null && $marginRight !== '') {
                    $this->pageMargins['right'] = (float)$marginRight;
                }

            }

        }

        if ($pdfData->autobreak_margin !== null && $pdfData->autobreak_margin !== '') {
            $this->pageMargins['autobreakMargin'] = (float)$pdfData->autobreak_margin;
        }

        if ($pdfData->header_margin !== null && $pdfData->header_margin !== '') {
            $this->pageMargins['headerMargin'] = (float)$pdfData->header_margin;
        }

        if ($pdfData->footer_margin !== null && $pdfData->footer_margin !== '') {
            $this->pageMargins['footerMargin'] = (float)$pdfData->footer_margin;
        }

        $footerglobalsize = StringUtil::deserialize($pdfData->footer_globalsize);
        $footerglobalfont = StringUtil::deserialize($pdfData->footer_globalfont);

        $this->footersesstingsarray = array(
            'valid' => ($pdfData->footer_text !== ''),
            'text' => $pdfData->footer_text,
            'font' => ($footerglobalfont[0] !== '' ? $footerglobalfont[0] : 'helvetica'),
            'fontstyle' => ($footerglobalfont[2]),
            'fontsize' => ($footerglobalfont[1] !== '' ? (int)$footerglobalfont[1] : '10'),
            'bottomoffset' => 10,
            'alignment' => ($footerglobalfont[3]),
            'width' => (int)$footerglobalsize[0],
            'height' => (int)$footerglobalsize[1]
        );

        $headerglobalsize = StringUtil::deserialize($pdfData->header_globalsize);
        $headerglobalfont = StringUtil::deserialize($pdfData->header_globalfont);

        $this->headersesstingsarray = array(
            'valid' => ($pdfData->header_text !== ''),
            'text' => $pdfData->header_text,
            'width' => (int)$headerglobalsize[0],
            'height' => (int)$headerglobalsize[1],
            'font' => ($headerglobalfont[0] !== '' ? $headerglobalfont[0] : 'helvetica'),
            'fontstyle' => ($headerglobalfont[2] !== '' ? $headerglobalfont[2] : 'B'),
            'fontsize' => ($headerglobalfont[1] !== '' ? (int)$headerglobalfont[1] : '10'),
            'alignment' => ($headerglobalfont[3])
        );

        $objFile = new File($path . '/' . $pdfname);
        if ($objFile->exists()) {
            $objFile->delete();
        }

        return $this->generate($pdfData->html, $pdfname, $path, $settingsarray);

    }

    /**
     * @param mixed $text
     * @param mixed $filename
     * @param mixed $path
     * @param mixed $settingsarray
     * @return string
     * @throws \Exception
     */
    public function generate(mixed $text, mixed $filename, mixed $path, mixed $settingsarray): string
    {
        ob_start();

        $l['a_meta_dir'] = 'ltr';
        $l['a_meta_charset'] = Config::get('characterSet');

        $locale = System::getContainer()->get('request_stack')->getCurrentRequest()?->getLocale();
        if ($locale === null) {
            $locale = 'de';
        }

        $l['a_meta_language'] = $locale;

        $l['w_page'] = 'page';

        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor($settingsarray['pdfauthor']);
        $this->SetTitle($settingsarray['pdftitel']);
        $this->SetSubject("");
        $this->SetKeywords("");
        $this->setFontSubsetting(false);

        foreach ($this->headersesstingsarray as $key => $value) {

            if ($key === 'text') {
                $value = str_replace($this->search_custom, $this->replace_custom, Utils::replaceInsertTags($value, false));
            }

            $this->setHeaderDataItem($key, $value);

        }

        foreach ($this->footersesstingsarray as $key => $value) {

            if ($key === 'text') {
                $value = str_replace($this->search_custom, $this->replace_custom, Utils::replaceInsertTags($value, false));
            }

            $this->setFooterDataItem($key, $value);

        }

        $this->setPrintHeader($this->getHeaderDataItem('valid'));
        $this->setPrintFooter($this->getFooterDataItem('valid'));

        $marginLeft = PDF_MARGIN_LEFT;
        if ($this->pageMargins['left'] !== null) {
            $marginLeft = (float)$this->pageMargins['left'];
        }

        $marginTop = PDF_MARGIN_TOP;
        if ($this->pageMargins['top'] !== null) {
            $marginTop = (float)$this->pageMargins['top'];
        }

        $marginRight = PDF_MARGIN_RIGHT;
        if ($this->pageMargins['right'] !== null) {
            $marginRight = (float)$this->pageMargins['right'];
        }

        $this->SetMargins($marginLeft, $marginTop + ($this->getHeaderDataItem('valid') === true ? (int)$this->getHeaderDataItem('height') : 0), $marginRight);

        $this->SetHeaderMargin(($this->pageMargins['headerMargin'] !== null ? (float)$this->pageMargins['headerMargin'] : PDF_MARGIN_HEADER));
        $this->SetFooterMargin(($this->pageMargins['footerMargin'] !== null ? (float)$this->pageMargins['footerMargin'] : PDF_MARGIN_FOOTER));

        $this->SetAutoPageBreak(true, ($this->pageMargins['autobreakMargin'] !== null ? (float)$this->pageMargins['autobreakMargin'] : PDF_MARGIN_BOTTOM + 10));

        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->setLanguageArray($l);
        $this->SetFont($settingsarray['font_family'], $settingsarray['font_style'], $settingsarray['font_size']);

        $this->AddPage();
        $html = str_replace($this->search_custom, $this->replace_custom, Utils::replaceInsertTags($text, false));

        // Check for pageBreak
        $pageSplit = \explode('##ad_pagebreak##', $html);
        $pageCount = \count($pageSplit);
        if ($pageCount > 1) {

            $this->writeHTML($pageSplit[0], true, false, true);
            for ($i = 1; $i < $pageCount; $i++) {

                $this->AddPage();
                $this->writeHTML($pageSplit[$i], true, false, true);

            }

        } else {
            $this->writeHTML($html, true, false, true);
        }

        $this->lastPage();

        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $xdir = $rootDir . "/" . $path;

        $filesystem = new Filesystem();

        try {
            $filesystem->mkdir($xdir, 0755);
        } catch (\Exception $e) {
            throw new \Exception(sprintf('Directory "%s" was not created: %s', $xdir, $e->getMessage()));
        }

        $this->Output($xdir . '/' . $filename, 'F');

        ob_end_clean();

        $resultPath = $path . '/' . $filename;

        $finalResultFile = new File($resultPath);
        if ($finalResultFile->exists() && Dbafs::shouldBeSynchronized($finalResultFile->path)) {
            Dbafs::addResource($finalResultFile->path);
        }

        return $resultPath;

    }
}
