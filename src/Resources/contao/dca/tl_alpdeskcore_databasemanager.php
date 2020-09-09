<?php

$GLOBALS['TL_DCA']['tl_alpdeskcore_databasemanager'] = array
    (
    'config' => array
        (
        'dataContainer' => 'Table',
        'ctable' => array('tl_alpdeskcore_databasemanager_tables'),
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => array
            (
            'keys' => array
                (
                'id' => 'primary',
                'title' => 'index',
            )
        ),
        'ondelete_callback' => array
            (
            array('Alpdesk\\AlpdeskCore\\Library\\Backend\\AlpdeskCoreDcaUtils', 'databasemanagerOnDeleteCallback'),
        ),
    ),
    'list' => array
        (
        'sorting' => array
            (
            'mode' => 2,
            'fields' => array('title ASC'),
            'flag' => 1,
            'panelLayout' => 'sort,search,limit'
        ),
        'label' => array
            (
            'fields' => array('title', 'host', 'database'),
            'showColumns' => true
        ),
        'operations' => array
            (
            'edit' => array
                (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['edit'],
                'href' => 'table=tl_alpdeskcore_databasemanager_tables',
                'icon' => 'edit.gif'
            ),
            'editheader' => array
                (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['editheader'],
                'href' => 'act=edit',
                'icon' => 'header.gif',
            ),
            'delete' => array
                (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            )
        )
    ),
    'palettes' => array
        (
        'default' => 'title;host,port;database,username,password;dbprefix;dbmigration'
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
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['title'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'host' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['host'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'default' => 'localhost',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'port' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['port'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'default' => '3306',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50', 'rgxp' => 'digit'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'database' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['database'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'username' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['username'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'password' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['password'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50', 'hideInput' => false),
            'save_callback' => array
                (
                array('Alpdesk\\AlpdeskCore\\Library\\Backend\\AlpdeskCoreDcaUtils', 'generateEncryptPassword')
            ),
            'load_callback' => array
                (
                array('Alpdesk\\AlpdeskCore\\Library\\Backend\\AlpdeskCoreDcaUtils', 'regenerateEncryptPassword')
            ),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'dbprefix' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['dbprefix'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'default' => 'alpdesk_',
            'eval' => array('mandatory' => false, 'maxlength' => 250, 'tl_class' => 'w50'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'dbmigration' => array
            (
            'exclude' => true,
            'inputType' => 'alpdeskcore_widget_databasemanager',
            'eval' => array('doNotSaveEmpty' => true)
        ),
    )
);
