<?php

$GLOBALS['TL_DCA']['tl_alpdeskcore_pdf'] = array
    (
    'config' => array
        (
        'dataContainer' => 'Table',
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
            'mode' => 2,
            'fields' => array('title ASC'),
            'flag' => 1,
            'panelLayout' => 'filter,search,limit'
        ),
        'label' => array
            (
            'fields' => array('title'),
            'showColumns' => true,
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
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf']['edit'],
                'href' => 'table=tl_alpdeskcore_pdf_elements',
                'icon' => 'edit.gif'
            ),
            'editheader' => array
                (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf']['editheader'],
                'href' => 'act=edit',
                'icon' => 'header.gif',
            ),
            'delete' => array
                (
                'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
            ),
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
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_pdf']['title'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'tl_class' => 'w50', 'maxlength' => 250),
            'sql' => "varchar(250) NOT NULL default ''"
        )
    )
);
