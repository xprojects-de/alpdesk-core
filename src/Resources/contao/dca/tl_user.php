<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('alpdeskcore_legend', 'elements_legend', PaletteManipulator::POSITION_BEFORE, true)
    ->addField('alpdeskcorelogs_enabled', 'alpdeskcore_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('extend', 'tl_user')
    ->applyToPalette('custom', 'tl_user');

$GLOBALS['TL_DCA']['tl_user']['fields']['alpdeskcorelogs_enabled'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['alpdeskcorelogs_enabled'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'reference' => &$GLOBALS['TL_LANG']['CTE'],
    'eval' => array('multiple' => false, 'helpwizard' => false, 'tl_class' => 'clr'),
    'sql' => "int(10) unsigned NOT NULL default '0'"
];
