<?php

$GLOBALS['TL_DCA']['tl_alpdeskcore_databasemanager_tables'] = array
    (
    'config' => array
        (
        'dataContainer' => 'Table',
        'ptable' => 'tl_alpdeskcore_databasemanager',
        'enableVersioning' => true,
        'sql' => array
            (
            'keys' => array
                (
                'id' => 'primary',
                'pid' => 'index'
            )
        ),
        'oncopy_callback' => array
            (
            array('Alpdesk\\AlpdeskCore\\Library\\Backend\\AlpdeskCoreDcaUtils', 'databasemanagerelementsOnCopyCallback'),
        ),
        'ondelete_callback' => array
            (
            array('Alpdesk\\AlpdeskCore\\Library\\Backend\\AlpdeskCoreDcaUtils', 'databasemanagerelementsOnDeleteCallback'),
        ),
    ),
    'list' => array
        (
        'sorting' => array
            (
            'mode' => 4,
            'fields' => array('sorting'),
            'headerFields' => array('title'),
            'panelLayout' => 'search,limit',
            'child_record_callback' => array('Alpdesk\\AlpdeskCore\\Library\\Backend\\AlpdeskCoreDcaUtils', 'listDatabasemanagerChildElements')
        ),
        'operations' => array
            (
            'edit' => array
                (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif'
            ),
            'cut' => array
                (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['cut'],
                'href' => 'act=paste&amp;mode=cut',
                'icon' => 'cut.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"'
            ),
            'copy' => array
                (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['copy'],
                'href' => 'act=paste&amp;mode=copy',
                'icon' => 'copy.gif'
            ),
            'delete' => array
                (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            )
        )
    ),
    'palettes' => array
        (
        '__selector__' => array(),
        'default' => 'dbtable;dbindex;dbfields'
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
        'dbtable' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['dbtable'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'dbindex' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['dbindex'],
            'exclude' => true,
            'inputType' => 'multiColumnWizard',
            'eval' => [
                'columnFields' => [
                    'indextype' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['indextype'],
                        'exclude' => true,
                        'inputType' => 'select',
                        'eval' => [
                            'style' => 'width:150px',
                            'includeBlankOption' => false,
                        ],
                        'reference' => [
                            '0' => 'INDEX',
                            '1' => 'UNIQUE INDEX',
                        ],
                        'options' => [0, 1]
                    ],
                    'indexfields' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['indexfields'],
                        'exclude' => true,
                        'inputType' => 'text',
                        'eval' => ['style' => 'width:400px'],
                    ],
                    'indexname' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['indexname'],
                        'exclude' => true,
                        'inputType' => 'text',
                        'eval' => ['style' => 'width:300px'],
                    ],
                ],
            ],
            'sql' => 'blob NULL',
        ),
        'dbfields' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['dbfields'],
            'exclude' => true,
            'inputType' => 'fieldpalette',
            'foreignKey' => 'tl_fieldpalette.id',
            'relation' => array('type' => 'hasMany', 'load' => 'eager'),
            'sql' => "blob NULL",
            'fieldpalette' => array
                (
                'config' => array(
                    'hidePublished' => false,
                    'sql' => array
                        (
                        'keys' => array
                            (
                            'id' => 'primary',
                        )
                    )
                ),
                'list' => array
                    (
                    'label' => array
                        (
                        'fields' => array('databasemanager_name', 'databasemanager_type'),
                        'format' => '%s, %s',
                    ),
                ),
                'palettes' => array
                    (
                    'default' => 'databasemanager_name;databasemanager_type,databasemanager_length,databasemanager_null;databasemanager_adddefault,databasemanager_default;databasemanager_autoincrement,databasemanager_primary,databasemanager_unsigned',
                ),
                'fields' => array
                    (
                    'databasemanager_name' => array
                        (
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['databasemanager_name'],
                        'exclude' => true,
                        'filter' => true,
                        'inputType' => 'text',
                        'eval' => array('tl_class' => 'w50', 'mandatory' => true),
                        'sql' => "varchar(255) NOT NULL default ''"
                    ),
                    'databasemanager_type' => array
                        (
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['databasemanager_type'],
                        'exclude' => true,
                        'filter' => true,
                        'inputType' => 'select',
                        'options' => array('char', 'binary', 'float', 'double', 'real', 'numeric', 'decimal', 'tinytext', 'text', 'mediumtext', 'tinyblob', 'blob', 'mediumblob', 'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'year', 'varchar'),
                        'reference' => array('char', 'binary', 'float', 'double', 'real', 'numeric', 'decimal', 'tinytext', 'text', 'mediumtext', 'tinyblob', 'blob', 'mediumblob', 'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'year', 'varchar'),
                        'eval' => array('chosen' => true, 'tl_class' => 'w50'),
                        'sql' => "varchar(100) NOT NULL default ''"
                    ),
                    'databasemanager_length' => array
                        (
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['databasemanager_length'],
                        'exclude' => true,
                        'filter' => true,
                        'inputType' => 'text',
                        'eval' => array('tl_class' => 'w50', 'rgxp' => 'digit'),
                        'sql' => "int(10) unsigned NOT NULL default '0'"
                    ),
                    'databasemanager_null' => array
                        (
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['databasemanager_null'],
                        'exclude' => true,
                        'filter' => true,
                        'inputType' => 'select',
                        'options' => array('NOT NULL', 'NULL'),
                        'reference' => array('NOT NULL', 'NULL'),
                        'eval' => array('chosen' => true, 'tl_class' => 'w50'),
                        'sql' => "varchar(100) NOT NULL default ''"
                    ),
                    'databasemanager_adddefault' => array
                        (
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['databasemanager_adddefault'],
                        'exclude' => true,
                        'filter' => true,
                        'inputType' => 'checkbox',
                        'eval' => array('tl_class' => 'w50'),
                        'sql' => "char(1) NOT NULL default ''"
                    ),
                    'databasemanager_default' => array
                        (
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['databasemanager_default'],
                        'exclude' => true,
                        'filter' => true,
                        'inputType' => 'text',
                        'eval' => array('tl_class' => 'w50'),
                        'sql' => "varchar(100) NOT NULL default ''"
                    ),
                    'databasemanager_autoincrement' => array
                        (
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['databasemanager_autoincrement'],
                        'exclude' => true,
                        'filter' => true,
                        'inputType' => 'checkbox',
                        'eval' => array('tl_class' => 'w50'),
                        'sql' => "char(1) NOT NULL default ''"
                    ),
                    'databasemanager_primary' => array
                        (
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['databasemanager_primary'],
                        'exclude' => true,
                        'filter' => true,
                        'inputType' => 'checkbox',
                        'eval' => array('tl_class' => 'w50'),
                        'sql' => "char(1) NOT NULL default ''"
                    ),
                    'databasemanager_unsigned' => array
                        (
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager_tables']['databasemanager_unsigned'],
                        'exclude' => true,
                        'filter' => true,
                        'inputType' => 'checkbox',
                        'eval' => array('tl_class' => 'w50'),
                        'sql' => "char(1) NOT NULL default ''"
                    ),
                ),
            )
        ),
    )
);
