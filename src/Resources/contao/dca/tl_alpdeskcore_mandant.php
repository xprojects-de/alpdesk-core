<?php

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_alpdeskcore_mandant'] = array
(
    'config' => array
    (
        'dataContainer' => DC_Table::class,
        'ctable' => array('tl_alpdeskcore_mandant_elements'),
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary'
            )
        ),
    ),
    'list' => array
    (
        'sorting' => array
        (
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => array('mandant ASC'),
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'search,limit'
        ),
        'label' => array
        (
            'fields' => array('mandant'),
            'showColumns' => true,
        ),
        'operations' => array
        (
            'edit',
            'editheader',
            'delete'
        )
    ),
    'palettes' => array
    (
        'default' => 'mandant;filemount'
    ),
    'fields' => array
    (
        'id' => array
        (
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ),
        'tstamp' => array
        (
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'mandant' => array
        (
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => array('alpdesk_apishow' => true, 'mandatory' => true, 'tl_class' => 'w50', 'maxlength' => 250),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'filemount' => array
        (
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => array('multiple' => false, 'fieldType' => 'radio', 'mandatory' => true),
            'sql' => "blob NULL"
        ),
    )
);
