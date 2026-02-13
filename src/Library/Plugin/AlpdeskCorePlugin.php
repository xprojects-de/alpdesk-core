<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Plugin;

use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCorePluginException;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantElementsModel;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Contao\FilesModel;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreInputSecurity;
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
                if ((string)$pluginElement->type === $plugin) {
                    if (!\in_array((string)$pluginElement->type, $invalidElements, true)) {
                        $validPlugin = true;
                    }
                    break;
                }
            }

            if ($validPlugin === false) {
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

            $fileMount = $mandantInfo->filemount ?? null;
            if ($fileMount !== null) {

                $rootPath = FilesModel::findByUuid($mandantInfo->filemount);
                $pathRootPath = $rootPath->path ?? '';

                $mInfo->setFilemountmandant_uuid($mandantInfo->filemount);
                $mInfo->setFilemountmandant_path($pathRootPath);
                $mInfo->setFilemountmandant_rootpath($this->rootDir . '/' . $pathRootPath);

                $mInfo->setFilemount_uuid($mandantInfo->filemount);
                $mInfo->setFilemount_path($pathRootPath);
                $mInfo->setFilemount_rootpath($this->rootDir . '/' . $pathRootPath);

            }

            if ($user->getHomeDir() !== null) {

                $rootPathMember = FilesModel::findByUuid($user->getHomeDir());
                if ($rootPathMember !== null) {
                    $mInfo->setFilemount_uuid($rootPathMember->uuid);
                    $mInfo->setFilemount_path($rootPathMember->path);
                    $mInfo->setFilemount_rootpath($this->rootDir . '/' . $rootPathMember->path);
                }
            }

            $mInfo->setId((int)$mandantInfo->id);
            $mInfo->setMemberId($user->getMemberId());
            $mInfo->setMandant($mandantInfo->mandant);
            $mInfo->setAccessDownload($user->getAccessDownload());
            $mInfo->setAccessUpload($user->getAccessUpload());
            $mInfo->setAccessCreate($user->getAccessCreate());
            $mInfo->setAccessDelete($user->getAccessDelete());
            $mInfo->setAccessRename($user->getAccessRename());
            $mInfo->setAccessMove($user->getAccessMove());
            $mInfo->setAccessCopy($user->getAccessCopy());
            $mInfo->setCrudOperations($user->getCrudOperations());
            $mInfo->setCrudTables($user->getCrudTables());
            $mInfo->setAdditionalDatabaseInformation($mandantInfo->row());

            return $mInfo;

        }

        throw new AlpdeskCorePluginException('cannot get Mandantinformations', AlpdeskCoreConstants::$ERROR_INVALID_MANDANT);

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

        return $response;

    }
}
