<?php

use Alpdesk\AlpdeskCore\Library\Backend\AlpdeskCoreDcaUtils;
use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_alpdeskcore_pdf_elements'] = array
(
    'config' => array
    (
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_alpdeskcore_pdf',
        'enableVersioning' => true,
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary',
                'pid' => 'index'
            )
        ),
        'onload_callback' => array
        (
            array(AlpdeskCoreDcaUtils::class, 'pdfElementsloadCallback')
        ),
    ),
    'list' => array
    (
        'sorting' => array
        (
            'mode' => DataContainer::MODE_PARENT,
            'fields' => array('sorting'),
            'headerFields' => array('title'),
            'panelLayout' => 'filter;search,limit',
            'child_record_callback' => array(AlpdeskCoreDcaUtils::class, 'listPDFElements')
        ),
        'global_operations' => array
        (
            'all' => array
            (
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations' => array
        (
            'edit' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif'
            ),
            'copy' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.gif'
            ),
            'delete' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false;Backend.getScrollOffset();"'
            ),
            'generatetestpdf' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['generatetestpdf'],
                'icon' => 'redirect.gif',
                'href' => 'act=generatetestpdf',
                'button_callback' => array(AlpdeskCoreDcaUtils::class, 'generatetestpdfLinkCallback')
            ),
        )
    ),
    'palettes' => array
    (
        '__selector__' => array(),
        'default' => 'name,pdfauthor,pdftitel,font,margins,autobreak_margin;html;header_text,header_globalsize,header_globalfont,header_margin;footer_text,footer_globalsize,footer_globalfont,footer_margin'
    ),
    'fields' => array
    (
        'id' => array
        (
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ),
        'pid' => array
        (
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'tstamp' => array
        (
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'sorting' => array
        (
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'name' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['name'],
            'inputType' => 'text',
            'exclude' => true,
            'search' => true,
            'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'pdfauthor' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['pdfauthor'],
            'inputType' => 'text',
            'exclude' => true,
            'search' => true,
            'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'margins' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['margins'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('multiple' => true, 'size' => 3, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'font' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['font'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('multiple' => true, 'size' => 3, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'autobreak_margin' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['autobreak_margin'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('multiple' => false, 'tl_class' => 'w50'),
            'sql' => "varchar(64) NOT NULL default ''"
        ),
        'html' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['html'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => array('allowHtml' => true, 'class' => 'monospace', 'rte' => 'ace|html', 'helpwizard' => true),
            'explanation' => 'insertTags',
            'sql' => "mediumtext NULL"
        ),
        'header_text' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['header_text'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => array('allowHtml' => true, 'class' => 'monospace', 'rte' => 'ace|html', 'helpwizard' => true),
            'explanation' => 'insertTags',
            'sql' => "mediumtext NULL"
        ),
        'header_globalsize' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['header_globalsize'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('multiple' => true, 'size' => 2, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'header_globalfont' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['header_globalfont'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('multiple' => true, 'size' => 4, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'header_margin' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['header_margin'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('multiple' => false, 'tl_class' => 'w50'),
            'sql' => "varchar(64) NOT NULL default ''"
        ),
        'footer_text' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['footer_text'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => array('allowHtml' => true, 'class' => 'monospace', 'rte' => 'ace|html', 'helpwizard' => true),
            'explanation' => 'insertTags',
            'sql' => "mediumtext NULL"
        ),
        'footer_globalsize' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['footer_globalsize'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('multiple' => true, 'size' => 2, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'footer_globalfont' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['footer_globalfont'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('multiple' => true, 'size' => 4, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'footer_margin' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf_elements']['footer_margin'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('multiple' => false, 'tl_class' => 'w50'),
            'sql' => "varchar(64) NOT NULL default ''"
        )
    )
);
