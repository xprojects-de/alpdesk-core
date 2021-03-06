<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Plugin;

use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCorePluginException;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantElementsModel;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Contao\FilesModel;
use Contao\StringUtil;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreInputSecurity;
use Alpdesk\AlpdeskCore\Elements\AlpdeskCoreElement;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;

class AlpdeskCorePlugin
{
    protected string $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * @param string $username
     * @param int $mandantPid
     * @param array $invalidElements
     * @param string $plugin
     * @throws AlpdeskCorePluginException
     */
    private function verifyPlugin(string $username, int $mandantPid, array $invalidElements, string $plugin): void
    {
        $plugins = AlpdeskcoreMandantElementsModel::findEnabledByPid($mandantPid);

        if ($plugins !== null) {

            $validPlugin = false;

            foreach ($plugins as $pluginElement) {
                if ($pluginElement->type == $plugin) {
                    if (!\in_array($pluginElement->type, $invalidElements)) {
                        $validPlugin = true;
                    }
                    break;
                }
            }

            if ($validPlugin == false) {
                $msg = 'error loading plugin: ' . $plugin . ' for username:' . $username;
                throw new AlpdeskCorePluginException($msg, AlpdeskCoreConstants::$ERROR_INVALID_PLUGIN);
            }

        } else {
            $msg = 'error loading plugin because null for username:' . $username;
            throw new AlpdeskCorePluginException($msg, AlpdeskCoreConstants::$ERROR_INVALID_PLUGIN);
        }
    }

    /**
     * @param AlpdeskcoreUser $user
     * @return AlpdescCoreBaseMandantInfo
     * @throws AlpdeskCorePluginException
     */
    private function getMandantInformation(AlpdeskcoreUser $user): AlpdescCoreBaseMandantInfo
    {
        $mandantInfo = AlpdeskcoreMandantModel::findById($user->getMandantPid());

        if ($mandantInfo !== null) {

            $mInfo = new AlpdescCoreBaseMandantInfo();

            $rootPath = FilesModel::findByUuid($mandantInfo->filemount);

            $mInfo->setFilemountmandant_uuid($mandantInfo->filemount);
            $mInfo->setFilemountmandant_path($rootPath->path);
            $mInfo->setFilemountmandant_rootpath($this->rootDir . '/' . $rootPath->path);

            $mInfo->setFilemount_uuid($mandantInfo->filemount);
            $mInfo->setFilemount_path($rootPath->path);
            $mInfo->setFilemount_rootpath($this->rootDir . '/' . $rootPath->path);

            if ($user->getHomeDir() !== null) {

                $rootPathMember = FilesModel::findByUuid($user->getHomeDir());
                if ($rootPathMember !== null) {
                    $mInfo->setFilemount_uuid($rootPathMember->uuid);
                    $mInfo->setFilemount_path($rootPathMember->path);
                    $mInfo->setFilemount_rootpath($this->rootDir . '/' . $rootPathMember->path);
                }
            }

            $mInfo->setId(\intval($mandantInfo->id));
            $mInfo->setMemberId($user->getMemberId());
            $mInfo->setMandant($mandantInfo->mandant);
            $mInfo->setAccessDownload($user->getAccessDownload());
            $mInfo->setAccessUpload($user->getAccessUpload());
            $mInfo->setAccessCreate($user->getAccessCreate());
            $mInfo->setAccessDelete($user->getAccessDelete());
            $mInfo->setAccessRename($user->getAccessRename());
            $mInfo->setAccessMove($user->getAccessMove());
            $mInfo->setAccessCopy($user->getAccessCopy());
            $mInfo->setAdditionalDatabaseInformation($mandantInfo->row());

            return $mInfo;

        } else {
            throw new AlpdeskCorePluginException('cannot get Mandantinformations', AlpdeskCoreConstants::$ERROR_INVALID_MANDANT);
        }
    }

    /**
     * @param AlpdeskcoreUser $user
     * @param array $plugindata
     * @return AlpdeskCorePlugincallResponse
     * @throws \Exception
     */
    public function call(AlpdeskcoreUser $user, array $plugindata): AlpdeskCorePlugincallResponse
    {
        if (!\array_key_exists('plugin', $plugindata) || !\array_key_exists('data', $plugindata)) {
            $msg = 'invalid key-parameters for plugin';
            throw new AlpdeskCorePluginException($msg, AlpdeskCoreConstants::$ERROR_INVALID_KEYPARAMETERS);
        }

        $plugin = (string)AlpdeskcoreInputSecurity::secureValue($plugindata['plugin']);

        $data = (array)$plugindata['data'];
        $this->verifyPlugin($user->getUsername(), $user->getMandantPid(), $user->getInvalidElements(), $plugin);
        $mandantInfo = $this->getMandantInformation($user);

        $response = new AlpdeskCorePlugincallResponse();
        $response->setUsername($user->getUsername());
        $response->setAlpdesk_token($user->getUsedToken());
        $response->setMandantInfo($mandantInfo);
        $response->setPlugin($plugin);
        $response->setRequestData($data);

        /**
         * @deprecated Deprecated since 1.0, to be removed in Contao 2.0; use the Symfony-Events instead => alpdesk.plugincall
         */
        if (isset($GLOBALS['TL_ADME'][$plugin])) {
            $c = new $GLOBALS['TL_ADME'][$plugin]();
            if ($c instanceof AlpdeskCoreElement) {
                $tmp = $c->execute($mandantInfo, $data);
                if ($c->getCustomTemplate() == true) {
                    if (!\array_key_exists('ngContent', $tmp) ||
                        !\array_key_exists('ngStylesheetUrl', $tmp) ||
                        !\array_key_exists('ngScriptUrl', $tmp)) {
                        $msg = 'plugin use customTemplate but keys not defined in resultArray for plugin:' . $plugin;
                        throw new AlpdeskCorePluginException($msg);
                    }
                    $tmp['ngContent'] = StringUtil::convertEncoding($tmp['ngContent'], 'UTF-8');
                }
                $response->setData($tmp);
            } else {
                $msg = 'plugin entrypoint wrong classtype for plugin:' . $plugin . ' and username:' . $user->getUsername();
                throw new AlpdeskCorePluginException($msg);
            }
        }

        return $response;
    }
}
