<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
        ->addField('alpdeskcore_fixtoken', 'login_legend', PaletteManipulator::POSITION_APPEND)
        ->applyToSubpalette('login', 'tl_member');

$GLOBALS['TL_DCA']['tl_member']['fields']['alpdeskcore_fixtoken'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_member']['alpdeskcore_fixtoken'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'text',
    'eval' => ['unique' => true, 'doNotCopy' => true, 'tl_class' => 'w50 clr'],
    'save_callback' => [
        ['Alpdesk\\AlpdeskCore\\Library\\Backend\\AlpdeskCoreDcaUtils', 'generateFixToken']
    ],
    'sql' => "text NULL"
];
