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
            'panelLayout' => 'limit'
        ),
        'operations' => array
        (
            'edit',
            'copy',
            'cut',
            'delete'
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
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'eval' => array('chosen' => true, 'submitOnChange' => true, 'tl_class' => 'w50'),
            'sql' => array('name' => 'type', 'type' => 'string', 'length' => 64, 'default' => '')
        ),
        'invisible' => array
        (
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
            'sql' => "char(1) NOT NULL default ''"
        ),
        'disabled' => array
        (
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
            'sql' => "char(1) NOT NULL default ''"
        ),
    )
);
