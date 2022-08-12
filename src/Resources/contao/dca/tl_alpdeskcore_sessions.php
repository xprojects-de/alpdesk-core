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
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_sessions']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif'
            ),
            'delete' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_sessions']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
            )
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
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_sessions']['username'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50'),
            'sql' => "varchar(250) NOT NULL default ''"
        ),
        'token' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_sessions']['token'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 1000, 'tl_class' => 'w50'),
            'sql' => "text NULL"
        ),
        'refresh_token' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_sessions']['refresh_token'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 1000, 'tl_class' => 'w50'),
            'sql' => "text NULL"
        )
    )
);
