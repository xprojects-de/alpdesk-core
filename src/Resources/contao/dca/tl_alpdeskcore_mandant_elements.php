<?php

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_alpdeskcore_mandant_elements'] = array
(
    'config' => array
    (
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_alpdeskcore_mandant',
        'enableVersioning' => true,
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary',
                'pid' => 'index',
                'pid,disabled' => 'index',
                'pid,disabled,invisible' => 'index',
            )
        )
    ),
    'list' => array
    (
        'sorting' => array
        (
            'mode' => DataContainer::MODE_PARENT,
            'fields' => array('sorting'),
            'headerFields' => array('mandant'),
            'panelLayout' => 'limit',
            //'child_record_callback' => Done using contao.callback event
        ),
        'operations' => array
        (
            'edit' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant_elements']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif'
            ),
            'copy' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant_elements']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.gif'
            ),
            'cut' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant_elements']['cut'],
                'href' => 'act=paste&amp;mode=cut',
                'icon' => 'cut.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"'
            ),
            'delete' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant_elements']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
            )
        )
    ),
    'palettes' => array
    (
        '__selector__' => array('type'),
        'default' => 'type;invisible,disabled'
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
        'type' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant_elements']['type'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            //'options_callback' => Done using contao.callback event
            'eval' => array('chosen' => true, 'submitOnChange' => true, 'tl_class' => 'w50'),
            'sql' => array('name' => 'type', 'type' => 'string', 'length' => 64, 'default' => '')
        ),
        'invisible' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant_elements']['invisible'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
            'sql' => "char(1) NOT NULL default ''"
        ),
        'disabled' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant_elements']['disabled'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
            'sql' => "char(1) NOT NULL default ''"
        ),
    )
);
