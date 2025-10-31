<?php

use Alpdesk\AlpdeskCore\Library\Backend\AlpdeskCoreDcaUtils;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_alpdeskcore_databasemanager'] = array
(
    'config' => array
    (
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary'
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
            'panelLayout' => 'sort,search,limit'
        ),
        'label' => array
        (
            'fields' => array('title', 'host', 'database'),
            'showColumns' => true
        ),
        'operations' => array
        (
            'edit',
            'delete',
            'backupDatabase' => array
            (
                'route' => 'alpdesk_database_backend',
                'prefetch' => false,
                'attributes' => (new HtmlAttributes())->set('data-turbo', 'false'),
                'icon' => 'theme_export.svg'
            )
        )
    ),
    'palettes' => array
    (
        'default' => 'title;host,port;database,username,password;databasemodel;dbmigration'
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
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'host' => array
        (
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'default' => 'localhost',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'port' => array
        (
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'default' => '3306',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50', 'rgxp' => 'digit'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'database' => array
        (
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'username' => array
        (
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'password' => array
        (
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50', 'hideInput' => false),
            'save_callback' => array
            (
                array(AlpdeskCoreDcaUtils::class, 'generateEncryptPassword')
            ),
            'load_callback' => array
            (
                array(AlpdeskCoreDcaUtils::class, 'regenerateEncryptPassword')
            ),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'databasemodel' => array
        (
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => [
                'filesOnly' => true,
                'fieldType' => 'radio',
                'tl_class' => 'clr',
                'extensions' => 'json'
            ],
            'sql' => "binary(16) NULL"
        ),
        'dbmigration' => array
        (
            'exclude' => true,
            'inputType' => 'alpdeskcore_widget_databasemanager',
            'eval' => array('doNotSaveEmpty' => true)
        ),
    )
);
