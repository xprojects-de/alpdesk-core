<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Mandant;

use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreMandantException;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantElementsModel;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdeskCoreMandantResponse;
use Contao\StringUtil;
use Contao\System;
use Contao\Controller;
use Alpdesk\AlpdeskCore\Elements\AlpdeskCoreElement;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;

class AlpdeskCoreMandant {

  private function getPlugins(int $mandantPid, array $invalidElements): array {
    $data = array();
    $plugins = AlpdeskcoreMandantElementsModel::findEnabledAndVisibleByPid($mandantPid);
    if ($plugins !== null) {
      // @ToDo load for other languages
      System::loadLanguageFile('modules', 'de');
      foreach ($plugins as $pluginElement) {
        $type = (string) $pluginElement->type;
        if (!\in_array($type, $invalidElements)) {
          if (isset($GLOBALS['TL_ADME'][$type])) {
            $c = new $GLOBALS['TL_ADME'][$type]();
            if ($c instanceof AlpdeskCoreElement) {
              $customTemplate = false;
              if ($c->getCustomTemplate() == true) {
                $customTemplate = true;
              }
              \array_push($data, array(
                  'value' => $pluginElement->type,
                  'label' => $GLOBALS['TL_LANG']['ADME'][$pluginElement->type],
                  'customTemplate' => $customTemplate
              ));
            }
          }
        }
      }
    } else {
      throw new AlpdeskCoreMandantException("error loading plugins for Mandant");
    }
    return $data;
  }

  private function getData(int $mandantPid): array {
    $mData = AlpdeskcoreMandantModel::findById($mandantPid);
    if ($mData !== null) {
      $data = $mData->row();
      unset($data['id']);
      unset($data['tstamp']);
      $returnData = array();
      // @ToDo load for other languages
      System::loadLanguageFile('tl_alpdeskcore_mandant', 'de');
      Controller::loadDataContainer('tl_alpdeskcore_mandant');
      foreach ($data as $key => $value) {
        if (isset($GLOBALS['TL_DCA']['tl_alpdeskcore_mandant']['fields'][$key]['eval']['alpdesk_apishow']) && $GLOBALS['TL_DCA']['tl_alpdeskcore_mandant']['fields'][$key]['eval']['alpdesk_apishow'] == true) {
          $returnData[$key] = array(
              'value' => StringUtil::convertEncoding(StringUtil::deserialize($value), 'UTF-8'),
              'label' => $GLOBALS['TL_LANG']['tl_alpdeskcore_mandant'][$key][0],
          );
        }
      }
      return $returnData;
    } else {
      throw new AlpdeskCoreMandantException("error loading plugins for Mandant");
    }
  }

  public function list(AlpdeskcoreUser $user): AlpdeskCoreMandantResponse {
    $pluginData = $this->getPlugins($user->getMandantPid(), $user->getInvalidElements());
    $dataData = $this->getData($user->getMandantPid());
    $response = new AlpdeskCoreMandantResponse();
    $response->setUsername($user->getUsername());
    $response->setAlpdesk_token($user->getUsedToken());
    $response->setMandantId($user->getMandantPid());
    $response->setMemberId($user->getMemberId());
    $response->setFirstname($user->getFirstname());
    $response->setLastname($user->getLastname());
    $response->setEmail($user->getEmail());
    $response->setAccessDownload($user->getAccessDownload());
    $response->setAccessUpload($user->getAccessUpload());
    $response->setAccessCreate($user->getAccessCreate());
    $response->setAccessDelete($user->getAccessDelete());
    $response->setAccessRename($user->getAccessRename());
    $response->setAccessMove($user->getAccessMove());
    $response->setAccessCopy($user->getAccessCopy());
    $response->setPlugins($pluginData);
    $response->setData($dataData);
    return $response;
  }

}
