<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
        ->addField('alpdeskcore_mandant', 'login_legend', PaletteManipulator::POSITION_APPEND)
        ->addField('alpdeskcore_fixtoken', 'login_legend', PaletteManipulator::POSITION_APPEND)
        ->addField('alpdeskcore_elements', 'login_legend', PaletteManipulator::POSITION_APPEND)
        ->applyToSubpalette('login', 'tl_member');

$GLOBALS['TL_DCA']['tl_member']['config']['sql']['keys']['disable,login,username,alpdeskcore_mandant'] = 'index';

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
        ['Alpdesk\\AlpdeskCore\\Library\\Backend\\AlpdeskCoreDcaUtils', 'generateFixToken']
    ],
    'sql' => "text NULL"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_elements'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_elements'],
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkbox',
    'options_callback' => ['Alpdesk\\AlpdeskCore\\Library\\Backend\\AlpdeskCoreDcaUtils', 'getMandantElements'],
    'reference' => &$GLOBALS['TL_LANG']['ADME'],
    'eval' => ['tl_class' => 'clr', 'multiple' => true],
    'sql' => "blob NULL"
];
