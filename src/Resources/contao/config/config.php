<?php

use Alpdesk\AlpdeskCore\Model\Auth\AlpdeskcoreSessionsModel;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantElementsModel;
use Alpdesk\AlpdeskCore\Model\PDF\AlpdeskcorePdfElementsModel;
use Alpdesk\AlpdeskCore\Widget\AlpdeskcoreDatabasemanagerWidget;
use Alpdesk\AlpdeskCore\Model\Database\AlpdeskcoreDatabasemanagerTablesModel;
use Alpdesk\AlpdeskCore\Model\Database\AlpdeskcoreDatabasemanagerModel;

$GLOBALS['BE_FFL']['alpdeskcore_widget_databasemanager'] = AlpdeskcoreDatabasemanagerWidget::class;
if (TL_MODE == 'BE') {
  $GLOBALS['TL_CSS'][] = 'bundles/alpdeskcore/css/alpdeskcore_widget_databasemanager.css';
}

$GLOBALS['TL_MODELS']['tl_alpdeskcore_sessions'] = AlpdeskcoreSessionsModel::class;
$GLOBALS['TL_MODELS']['tl_alpdeskcore_mandant'] = AlpdeskcoreMandantModel::class;
$GLOBALS['TL_MODELS']['tl_alpdeskcore_mandant_elements'] = AlpdeskcoreMandantElementsModel::class;
$GLOBALS['TL_MODELS']['tl_alpdeskcore_pdf_elements'] = AlpdeskcorePdfElementsModel::class;
$GLOBALS['TL_MODELS']['tl_alpdeskcore_databasemanager_tables'] = AlpdeskcoreDatabasemanagerTablesModel::class;
$GLOBALS['TL_MODELS']['tl_alpdeskcore_databasemanager'] = AlpdeskcoreDatabasemanagerModel::class;

$GLOBALS['BE_MOD']['alpdeskcore']['alpdeskcore_sessions'] = array(
    'tables' => array(
        'tl_alpdeskcore_sessions'
    )
);

$GLOBALS['BE_MOD']['alpdeskcore']['alpdeskcore_mandant'] = array(
    'tables' => array(
        'tl_alpdeskcore_mandant',
        'tl_alpdeskcore_mandant_elements'
    )
);

$GLOBALS['BE_MOD']['alpdeskcore']['alpdeskcore_databasemanager'] = array(
    'tables' => array(
        'tl_alpdeskcore_databasemanager',
        'tl_alpdeskcore_databasemanager_tables'
    )
);

$GLOBALS['BE_MOD']['alpdeskcore']['alpdeskcore_pdf'] = array(
    'tables' => array(
        'tl_alpdeskcore_pdf',
        'tl_alpdeskcore_pdf_elements'
    )
);

$GLOBALS['TL_ADME'] = array(
    'hello' => 'Alpdesk\\AlpdeskCore\\Elements\\Hello\\AlpdeskCoreElementHello',
    'customTemplate' => 'Alpdesk\\AlpdeskCore\\Elements\\CustomTemplate\\AlpdeskCoreElementCustomTemplate'
);

