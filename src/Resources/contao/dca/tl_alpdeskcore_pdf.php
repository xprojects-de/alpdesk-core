<?php

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_alpdeskcore_pdf'] = array
(
    'config' => array
    (
        'dataContainer' => DC_Table::class,
        'ctable' => array('tl_alpdeskcore_pdf_elements'),
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary',
                'title' => 'index',
            )
        )
    ),
    'list' => array
    (
        'sorting' => array
        (
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => array('title ASC'),
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter,search,limit'
        ),
        'label' => array
        (
            'fields' => array('title'),
            'showColumns' => true,
        ),
        'global_operations' => array
        (
            'all'
        ),
        'operations' => array
        (
            'edit',
            'children',
            'delete'
        )
    ),
    'palettes' => array
    (
        'default' => 'title'
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
        'title' => array
        (
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'tl_class' => 'w50', 'maxlength' => 250),
            'sql' => "varchar(250) NOT NULL default ''"
        )
    )
);
