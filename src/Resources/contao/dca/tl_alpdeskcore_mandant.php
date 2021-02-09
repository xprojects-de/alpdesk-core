<?php

use Contao\Backend;
use Contao\MemberModel;

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
        'default' => 'mandant;member_1,member_2,member_3,member_4,member_5,member_6,member_7,member_8,member_9,member_10;filemount'
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
        'member_1' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['member'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_alpdeskcore_mandant', 'getMembers'],
            'eval' => array('mandatory' => false, 'tl_class' => 'w50', 'multiple' => false, 'includeBlankOption' => true, 'unique' => true),
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'member_2' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['member'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_alpdeskcore_mandant', 'getMembers'],
            'eval' => array('mandatory' => false, 'tl_class' => 'w50', 'multiple' => false, 'includeBlankOption' => true, 'unique' => true),
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'member_3' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['member'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_alpdeskcore_mandant', 'getMembers'],
            'eval' => array('mandatory' => false, 'tl_class' => 'w50', 'multiple' => false, 'includeBlankOption' => true, 'unique' => true),
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'member_4' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['member'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_alpdeskcore_mandant', 'getMembers'],
            'eval' => array('mandatory' => false, 'tl_class' => 'w50', 'multiple' => false, 'includeBlankOption' => true, 'unique' => true),
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'member_5' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['member'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_alpdeskcore_mandant', 'getMembers'],
            'eval' => array('mandatory' => false, 'tl_class' => 'w50', 'multiple' => false, 'includeBlankOption' => true, 'unique' => true),
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'member_6' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['member'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_alpdeskcore_mandant', 'getMembers'],
            'eval' => array('mandatory' => false, 'tl_class' => 'w50', 'multiple' => false, 'includeBlankOption' => true, 'unique' => true),
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'member_7' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['member'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_alpdeskcore_mandant', 'getMembers'],
            'eval' => array('mandatory' => false, 'tl_class' => 'w50', 'multiple' => false, 'includeBlankOption' => true, 'unique' => true),
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'member_8' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['member'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_alpdeskcore_mandant', 'getMembers'],
            'eval' => array('mandatory' => false, 'tl_class' => 'w50', 'multiple' => false, 'includeBlankOption' => true, 'unique' => true),
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'member_9' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['member'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_alpdeskcore_mandant', 'getMembers'],
            'eval' => array('mandatory' => false, 'tl_class' => 'w50', 'multiple' => false, 'includeBlankOption' => true, 'unique' => true),
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'member_10' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['member'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_alpdeskcore_mandant', 'getMembers'],
            'eval' => array('mandatory' => false, 'tl_class' => 'w50', 'multiple' => false, 'includeBlankOption' => true, 'unique' => true),
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'filemount' => array
            (
            'label' => &$GLOBALS['TL_LANG']['tl_alpdeskcore_mandant']['filemount'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => array('multiple' => false, 'fieldType' => 'radio', 'mandatory' => true),
            'sql' => "blob NULL"
        ),
    )
);

class tl_alpdeskcore_mandant extends Backend {

  public function getMembers(): array {
    $data = [];

    $memberObject = MemberModel::findBy(['tl_member.disable!=?', 'tl_member.login=?'], [1, 1]);
    if ($memberObject !== null) {
      foreach ($memberObject as $member) {
        $data[$member->id] = $member->firstname . ' ' . $member->lastname . ' (' . $member->username . ')';
      }
    }

    return $data;
  }

}
