<?php

use Alpdesk\AlpdeskCore\Library\Backend\AlpdeskCoreDcaUtils;
use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addField('alpdeskcore_mandant', 'login_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('alpdeskcore_fixtoken', 'login_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('alpdeskcore_elements', 'login_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('alpdeskcore_admin', 'login_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('alpdeskcore_mandantwhitelist', 'login_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('alpdeskcore_crudOperations', 'login_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('alpdeskcore_crudTables', 'login_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToSubpalette('login', 'tl_member');

PaletteManipulator::create()
    ->addField('alpdeskcore_upload', 'homedir_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('alpdeskcore_download', 'homedir_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('alpdeskcore_create', 'homedir_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('alpdeskcore_delete', 'homedir_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('alpdeskcore_rename', 'homedir_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('alpdeskcore_move', 'homedir_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('alpdeskcore_copy', 'homedir_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_member');

$GLOBALS['TL_DCA']['tl_member']['config']['sql']['keys']['disable,login,username'] = 'index';

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_mandant'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_mandant'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_alpdeskcore_mandant.mandant',
    'eval' => ['tl_class' => 'w50', 'mandantory' => false, 'multiple' => false, 'includeBlankOption' => true],
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_fixtoken'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_fixtoken'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'text',
    'eval' => ['unique' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
    'save_callback' => [
        [AlpdeskCoreDcaUtils::class, 'generateFixToken']
    ],
    'sql' => "text NULL"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_elements'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_elements'],
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkbox',
    'reference' => &$GLOBALS['TL_LANG']['ADME'],
    'eval' => ['tl_class' => 'clr', 'multiple' => true],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_admin'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_admin'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr', 'mandantory' => false, 'multiple' => false],
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_mandantwhitelist'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_mandantwhitelist'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_alpdeskcore_mandant.mandant',
    'eval' => ['tl_class' => 'clr', 'mandantory' => false, 'multiple' => true],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_upload'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_upload'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50', 'mandantory' => false, 'multiple' => false],
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_download'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_download'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50', 'mandantory' => false, 'multiple' => false],
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_create'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_create'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50', 'mandantory' => false, 'multiple' => false],
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_delete'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_delete'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50', 'mandantory' => false, 'multiple' => false],
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_rename'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_rename'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50', 'mandantory' => false, 'multiple' => false],
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_move'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_move'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50', 'mandantory' => false, 'multiple' => false],
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_copy'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_copy'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50', 'mandantory' => false, 'multiple' => false],
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_crudOperations'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_crudOperations'],
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkbox',
    'options' => [
        'schema' => 'Schema',
        'insert' => 'Create',
        'fetch' => 'Read',
        'update' => 'Update',
        'delete' => 'Delete'
    ],
    'eval' => ['tl_class' => 'clr', 'multiple' => true],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_crudTables'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_crudTables'],
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr', 'multiple' => true],
    'sql' => "blob NULL"
];
