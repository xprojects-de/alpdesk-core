<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Widget;

use Contao\Widget;
use Alpdesk\AlpdeskCore\Database\AlpdeskcoreConnectionFactory;

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
        $outputValue = $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['valid_parameters'] . '<br>';
        $connection = AlpdeskcoreConnectionFactory::create($host, $port, $username, $password, $database);
        try {
          if ($connection !== null) {
            $structure = AlpdeskcoreConnectionFactory::listTables($connection, $database);
            $outputValue .= $GLOBALS['TL_LANG']['tl_alpdeskcore_databasemanager']['valid_connection'] . '<br>';
            $outputValue .= '<hr>';
            foreach ($structure as $key => $value) {
              $outputValue .= '<div class="alpdeskcore_widget_databasemanager_table">';
              $outputValue .= '<strong>' . $key . '</strong>';
              if (is_array($value) && count($value) > 0) {
                $outputValue .= '<div class="alpdeskcore_widget_databasemanager_tablecolumns">';
                foreach ($value as $cKey => $cValue) {
                  $outputValue .= '<p' . (($cKey == 'options' || $cKey == 'primary' || $cKey == 'indexes') ? ' class="alpdeskcore_widget_databasemanager_specialtablecolumns"' : '') . '>';
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
    return '<div class="alpdeskcore_widget_databasemanager_container">' . $outputValue . '</div>';
  }

}
