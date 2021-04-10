<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('alpdeskcore_legend', 'elements_legend', PaletteManipulator::POSITION_BEFORE, true)
    ->addField('alpdeskcorelogs_enabled', 'alpdeskcore_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group');

$GLOBALS['TL_DCA']['tl_user_group']['fields']['alpdeskcorelogs_enabled'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user_group']['alpdeskcorelogs_enabled'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'reference' => &$GLOBALS['TL_LANG']['CTE'],
    'eval' => array('multiple' => false, 'helpwizard' => false, 'tl_class' => 'clr'),
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

