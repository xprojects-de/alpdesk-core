<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Widget;

use Contao\Widget;
use Contao\Input;
use Contao\Controller;
use Contao\Environment;
use Alpdesk\AlpdeskCore\Database\AlpdeskcoreConnectionFactory;
use Alpdesk\AlpdeskCore\Database\Migration\AlpdeskcoreConnectionMigrationCheck;
use Alpdesk\AlpdeskCore\Database\Migration\AlpdeskcoreConnectionMigration;
use Alpdesk\AlpdeskCore\Model\Database\AlpdeskcoreDatabasemanagerTablesModel;
use Alpdesk\AlpdeskCore\Model\Database\AlpdeskcoreDatabasemanagerModel;

class AlpdeskcoreDatabasemanagerWidget extends Widget {

  protected $blnSubmitInput = true;
  protected $blnForAttribute = true;
  protected $strTemplate = 'be_widget';

  public function generate(): string {
    $outputValue = '';
    if ($this->activeRecord !== null) {
      $host = $this->activeRecord->host;
      $port = intval($this->activeRecord->port);
      $username = $this->activeRecord->username;
      $password = $this->activeRecord->password;
      $database = $this->activeRecord->database;
      if ($host != '' && $port != '' && $username != '' && $password != '' && $database != '') {
        $outputValue = ' => ' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['valid_parameters'] . '<br>';
        $connection = AlpdeskcoreConnectionFactory::create($host, $port, $username, $password, $database);
        //$test = AlpdeskcoreDatabasemanagerModel::findById(intval($this->activeRecord->id));
        //$test = AlpdeskcoreDatabasemanagerModel::connectionById(intval($this->activeRecord->id));
        $connectionMigrationCheck = new AlpdeskcoreConnectionMigrationCheck();
        $connectionMigrationCheck->setConnection($connection);
        try {
          if ($connection !== null && $connectionMigrationCheck->canConnectToDatabase($database)) {
            $outputValue .= ' => ' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['valid_connection'] . '<br>';
            $connectionMigrationCheck->hasConfigurationError();
            $outputValue .= ' => ' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['no_configurationerros'] . '<br>';
            $fieldPalettesModel = new AlpdeskcoreDatabasemanagerTablesModel();
            $tablesInfo = $fieldPalettesModel->findPublishedElementsByPid(intval($this->activeRecord->id));
            $migrations = new AlpdeskcoreConnectionMigration($connection, $tablesInfo, $this->activeRecord->dbprefix);
            if (Input::get('alpdeskcore_dbmigration') == 1) {
              $migrationsResult = $migrations->showMigrations();
              if (count($migrationsResult) > 0) {
                $migrations->executeMigrations($migrationsResult);
              }
            } else if (Input::get('alpdeskcore_dbimport') == 1) {
              if ($tablesInfo === null) {
                $migrations->importMigrations(intval($this->activeRecord->id));
              }
            }
            $migrationsResult = $migrations->showMigrations();
            if (count($migrationsResult) > 0) {
              $outputValue .= '<hr>';
              foreach ($migrationsResult as $migration) {
                $outputValue .= '<p class="' . ((false === strpos(strtolower($migration), 'drop')) ? 'normal' : 'notice') . '">' . $migration . '</p>';
              }
              $outputValue .= '<hr>';
              $outputValue .= '<a class="bt_migrate" onclick="return confirm(\'' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['question_migrate'] . '\');" href="' . Controller::addToUrl('alpdeskcore_dbmigration=1') . '&rt=' . Input::get('rt') . '">' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['migratelink'] . '</a>';
              if ($tablesInfo === null) {
                $outputValue .= '&nbsp;<a class="bt_import" onclick="return confirm(\'' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['question_import'] . '\');" href="' . Controller::addToUrl('alpdeskcore_dbimport=1') . '&rt=' . Input::get('rt') . '">' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['importlink'] . '</a>';
              }
              $outputValue .= '<hr>';
            } else {
              $outputValue .= ' => ' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['dbok'] . '<br>';
            }
          } else {
            $outputValue .= ' => ' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['invalid_connection'] . '<br>';
          }
        } catch (\Exception $ex) {
          $outputValue .= ' => ' . $ex->getMessage() . '<br>';
        }
      } else {
        $outputValue .= ' => ' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['invalid_parameters'] . '<br>';
      }
    }
    return '<div class="alpdeskcore_widget_databasemanager_container">' . $outputValue . '</div>';
  }

}
