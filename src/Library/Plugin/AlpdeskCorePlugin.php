<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Plugin;

use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCorePluginException;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantElementsModel;
use Alpdesk\AlpdeskCore\Library\Plugin\AlpdeskCorePlugincallResponse;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Contao\FilesModel;
use Contao\StringUtil;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreInputSecurity;
use Alpdesk\AlpdeskCore\Elements\AlpdeskCoreElement;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;

class AlpdeskCorePlugin {

  protected string $rootDir;

  public function __construct(string $rootDir) {
    $this->rootDir = $rootDir;
  }

  private function verifyPlugin(string $username, int $mandantPid, string $plugin): void {
    $plugins = AlpdeskcoreMandantElementsModel::findEnabledByPid($mandantPid);
    if ($plugins !== null) {
      $validPlugin = false;
      foreach ($plugins as $pluginElement) {
        if ($pluginElement->type == $plugin) {
          $validPlugin = true;
          break;
        }
      }
      if ($validPlugin == false) {
        $msg = 'error loading plugin for username:' . $username;
        throw new AlpdeskCorePluginException($msg);
      }
    } else {
      $msg = 'error loading plugin because null for username:' . $username;
      throw new AlpdeskCorePluginException($msg);
    }
  }

  private function getMandantInformation($id): AlpdescCoreBaseMandantInfo {
    $mandantInfo = AlpdeskcoreMandantModel::findById($id);
    if ($mandantInfo !== null) {
      $rootPath = FilesModel::findByUuid($mandantInfo->filemount);
      $mInfo = new AlpdescCoreBaseMandantInfo();
      $mInfo->setId(intval($mandantInfo->id));
      $mInfo->setMandant($mandantInfo->mandant);
      $mInfo->setFilemount_uuid($mandantInfo->filemount);
      $mInfo->setFilemount_path($rootPath->path);
      $mInfo->setFilemount_rootpath($this->rootDir . '/' . $rootPath->path);
      $mInfo->setAdditionalDatabaseInformation($mandantInfo->row());
      return $mInfo;
    } else {
      $msg = 'cannot get Mandantinformations for username:' . $username;
      throw new AlpdeskCorePluginException($msg);
    }
  }

  public function call(AlpdeskcoreUser $user, array $plugindata): AlpdeskCorePlugincallResponse {
    if (!\array_key_exists('plugin', $plugindata) || !\array_key_exists('data', $plugindata)) {
      $msg = 'invalid key-parameters for plugin';
      throw new AlpdeskCorePluginException($msg);
    }
    $plugin = (string) AlpdeskcoreInputSecurity::secureValue($plugindata['plugin']);
    $data = (array) $plugindata['data'];
    $this->verifyPlugin($user->getUsername(), $user->getMandantPid(), $plugin);
    $mandantInfo = $this->getMandantInformation($user->getMandantPid());
    $response = new AlpdeskCorePlugincallResponse();
    $response->setUsername($user->getUsername());
    $response->setAlpdesk_token($user->getUsedToken());
    $response->setMandantInfo($mandantInfo);
    $response->setPlugin($plugin);
    if (isset($GLOBALS['TL_ADME'][$plugin])) {
      $c = new $GLOBALS['TL_ADME'][$plugin]();
      if ($c instanceof AlpdeskCoreElement) {
        $tmp = $c->execute($mandantInfo, $data);
        if ($c->getCustomTemplate() == true) {
          if (!\array_key_exists('ngContent', $tmp) ||
                  !\array_key_exists('ngStylesheetUrl', $tmp) ||
                  !\array_key_exists('ngScriptUrl', $tmp)) {
            $msg = 'plugin use customTemplate but keys not defined in resultArray for plugin:' . $plugin . ' and username:' . $username;
            throw new AlpdeskCorePluginException($msg);
          }
          $tmp['ngContent'] = StringUtil::convertEncoding($tmp['ngContent'], 'UTF-8');
        }
        $response->setData($tmp);
      } else {
        $msg = 'plugin entrypoint wrong classtype for plugin:' . $plugin . ' and username:' . $user->getUsername();
        throw new AlpdeskCorePluginException($msg);
      }
    } else {
      $msg = 'plugin not installed for plugin:' . $plugin . ' and username:' . $user->getUsername();
      throw new AlpdeskCorePluginException($msg);
    }
    return $response;
  }

}
