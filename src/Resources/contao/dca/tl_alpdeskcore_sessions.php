<?php

use Alpdesk\AlpdeskCore\Library\Backend\AlpdeskCoreDcaUtils;
use Contao\DC_Table;
use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_alpdeskcore_sessions'] = array
(
    'config' => array
    (
        'dataContainer' => DC_Table::class,
        'enableVersioning' => false,
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary',
                'username' => 'index'
            )
        )
    ),
    'list' => array
    (
        'sorting' => array
        (
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => array('username ASC'),
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;sort,search,limit'
        ),
        'label' => array
        (
            'fields' => array('username', 'token'),
            'showColumns' => true,
            'label_callback' => array(AlpdeskCoreDcaUtils::class, 'showSessionValid')
        ),
        'global_operations' => array
        (
            'all'
        ),
        'operations' => array
        (
            'edit',
            'delete'
        )
    ),
    'palettes' => array
    (
        'default' => 'username,token,refresh_token'
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
        'username' => array
        (
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'token' => array
        (
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 1000, 'tl_class' => 'w50'),
            'sql' => "text NULL"
        ),
        'refresh_token' => array
        (
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 1000, 'tl_class' => 'w50'),
            'sql' => "text NULL"
        )
    )
);
