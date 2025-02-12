<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Widget;

use Contao\Widget;
use Alpdesk\AlpdeskCore\Model\Database\AlpdeskcoreDatabasemanagerModel;
use Contao\FilesModel;
use Contao\File;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Alpdesk\AlpdeskCore\Database\AlpdeskcoreMigration;

class AlpdeskcoreDatabasemanagerWidget extends Widget
{
    protected $blnSubmitInput = true;
    protected $blnForAttribute = true;
    protected $strTemplate = 'be_widget';

    /**
     * @return string
     * @throws \Exception
     */
    public function generate(): string
    {
        $outputValue = '';
        $migrationOutput = '';

        if ($this->activeRecord !== null) {

            $id = (int)$this->activeRecord->id;
            $host = $this->activeRecord->host;
            $port = (int)$this->activeRecord->port;
            $username = $this->activeRecord->username;
            $password = $this->activeRecord->password;
            $database = $this->activeRecord->database;

            if ($host !== '' && $username !== '' && $password !== '' && $database !== '' && $port > 0) {

                $outputValue = $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['valid_parameters'] . '<br>';
                $connection = AlpdeskcoreDatabasemanagerModel::connectionById($id);

                try {

                    if ($connection !== null) {

                        $migrations = $this->checkMigrations($connection, $this->activeRecord->databasemodel);
                        if ($migrations !== "") {
                            $migrationOutput = '<div class="alpdeskcore_widget_databasemanager_container">' . $migrations . '</div>';
                        }

                        $structure = AlpdeskcoreDatabasemanagerModel::listTables($id, $database);
                        AlpdeskcoreDatabasemanagerModel::destroy($id);
                        $outputValue .= $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['valid_connection'] . '<br>';
                        $outputValue .= '<hr>';

                        foreach ($structure as $key => $value) {

                            $outputValue .= '<div class="alpdeskcore_widget_databasemanager_table">';
                            $outputValue .= '<strong>' . $key . '</strong>';
                            if (is_array($value) && count($value) > 0) {
                                $outputValue .= '<div class="alpdeskcore_widget_databasemanager_tablecolumns">';

                                foreach ($value as $cKey => $cValue) {

                                    $outputValue .= '<p' . (($cKey === 'options' || $cKey === 'primary' || $cKey === 'indexes') ? ' class="alpdeskcore_widget_databasemanager_specialtablecolumns"' : '') . '>';
                                    $outputValue .= '<strong>' . $cKey . '</strong><br>';
                                    $outputValue .= $cValue;
                                    $outputValue .= '</p>';

                                }

                                $outputValue .= '</div>';
                            }

                            $outputValue .= '</div>';
                        }
                    } else {
                        $outputValue .= $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['invalid_connection'] . '<br>';
                    }

                } catch (\Exception $ex) {
                    $outputValue .= $ex->getMessage() . '<br>';
                }

            } else {
                $outputValue .= $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['invalid_parameters'] . '<br>';
            }

        }

        return $migrationOutput . '<div class="alpdeskcore_widget_databasemanager_container">' . $outputValue . '</div>';

    }

    /**
     * @param Connection $connection
     * @param mixed $modelUuid
     * @return string
     */
    private function checkMigrations(Connection $connection, mixed $modelUuid): string
    {
        $migrations = '';

        try {

            if ($modelUuid !== null && $modelUuid !== '') {

                $jsonModelFile = FilesModel::findByUuid($modelUuid);
                if ($jsonModelFile !== null) {

                    $jsonFile = new File($jsonModelFile->path);
                    if ($jsonFile->exists()) {

                        // $jsonFile->getContent() must always be a valid JSON
                        $jsonModel = \json_decode($jsonFile->getContent(), true, 512, JSON_THROW_ON_ERROR);

                        if (\count($jsonModel) > 0) {

                            $dbmigfation = new AlpdeskcoreMigration($connection, $jsonModel);

                            $version = '-';
                            $dbmigfation->hasConfigurationError($version);
                            $migrations .= '<h3>' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['configurationcheck_valid'] . '</h3><br>Version: ' . $version;

                            $migrationItems = $dbmigfation->showMigrations();
                            if (\count($migrationItems) > 0) {

                                $hasToMigrate = Input::get('alpdeskcore_dbmigration');
                                if ($hasToMigrate !== null && (int)$hasToMigrate === 1) {
                                    $dbmigfation->executeMigrations($migrationItems);
                                    return $this->checkMigrations($connection, $modelUuid);
                                }

                                $migrations .= '<h3>' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['migrations'] . '</h3><hr>';
                                foreach ($migrationItems as $mig) {
                                    $migrations .= '<p>' . $mig . '</p>';
                                }
                                $migrations .= '<hr>';
                                $migrationButton = '<button data-controller="alpdeskcoredatabase" data-alpdeskcoredatabase-do-value="' . Input::get('do') . '" data-alpdeskcoredatabase-id-value="' . Input::get('id') . '" data-alpdeskcoredatabase-act-value="' . Input::get('act') . '" data-alpdeskcoredatabase-rt-value="' . Input::get('rt') . '" data-action="click->alpdeskcoredatabase#migrate" class="tl_submit">' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['migratelink'] . '</button>';
                                $migrations .= $migrationButton;
                            } else {
                                $migrations .= '<h3>' . $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['nomigrations'] . '</h3>';
                            }

                        }

                    }

                }

            }

        } catch (\Exception $ex) {
            $migrations = $ex->getMessage();
        }

        return $migrations;

    }

}
