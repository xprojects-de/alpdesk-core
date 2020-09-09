<?php

$GLOBALS['TL_DCA']['tl_alpdeskcore_mandant'] = array
    (
    'config' => array
        (
        'dataContainer' => 'Table',
        'ctable' => array('tl_alpdeskcore_mandant_elements'),
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => array
            (
            'keys' => array
                (
                'id' => 'primary',
                'mandant' => 'index',
            )
        ),
        'ondelete_callback' => array
            (
            array('Alpdesk\\AlpdeskCore\\Library\\Backend\\AlpdeskCoreDcaUtils', 'mandantOnDeleteCallback'),
        ),
    ),
    'list' => array
        (
        'sorting' => array
            (
            'mode' => 2,
            'fields' => array('mandant ASC'),
            'flag' => 1,
            'panelLayout' => 'search,limit'
        ),
        'label' => array
            (
            'fields' => array('mandant'),
            'showColumns' => true,
        ),
        'operations' => array
            (
            'edit' => array
                (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['edit'],
                'href' => 'table=tl_alpdeskcore_mandant_elements',
                'icon' => 'edit.gif'
            ),
            'editheader' => array
                (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['editheader'],
                'href' => 'act=edit',
                'icon' => 'header.gif',
            ),
            'delete' => array
                (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
            ),
        )
    ),
    'palettes' => array
        (
        'default' => 'mandant;auth;filemount'
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
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['mandant'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => array('alpdesk_apishow' => true, 'mandatory' => true, 'tl_class' => 'w50', 'maxlength' => 250),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'auth' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['auth'],
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
                            'username' => 'index',
                            'fixtoken' => 'index',
                            'username,pfield,ptable,published' => 'index',
                            'username,pfield,ptable,fixtoken,published' => 'index',
                        )
                    )
                ),
                'list' => array
                    (
                    'label' => array
                        (
                        'fields' => array('username'),
                        'format' => '%s',
                    ),
                ),
                'palettes' => array
                    (
                    'default' => 'username;password;fixtoken',
                ),
                'fields' => array
                    (
                    'username' => array
                        (
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['username'],
                        'search' => true,
                        'sorting' => true,
                        'flag' => 1,
                        'inputType' => 'text',
                        'eval' => array('mandatory' => true, 'rgxp' => 'extnd', 'nospace' => true, 'unique' => true, 'maxlength' => 64, 'tl_class' => 'w50'),
                        'sql' => "varchar(64) BINARY NULL"
                    ),
                    'password' => array
                        (
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['password'],
                        'exclude' => true,
                        'inputType' => 'password',
                        'eval' => array('mandatory' => true, 'preserveTags' => true, 'minlength' => Contao\Config::get('minPasswordLength')),
                        'sql' => "varchar(255) NOT NULL default ''"
                    ),
                    'fixtoken' => array
                        (
                        'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['fixtoken'],
                        'exclude' => true,
                        'search' => true,
                        'inputType' => 'text',
                        'eval' => array('unique' => true, 'doNotCopy' => true, 'tl_class' => 'w50 clr'),
                        'save_callback' => array(
                            array('Alpdesk\\AlpdeskCore\\Library\\Backend\\AlpdeskCoreDcaUtils', 'generateFixToken')
                        ),
                        'sql' => "text NULL"
                    ),
                ),
            )
        ),
        'filemount' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['filemount'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => array('multiple' => false, 'fieldType' => 'radio'),
            'sql' => "blob NULL"
        ),
    )
);
