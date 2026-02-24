<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Controller\Database;

use Alpdesk\AlpdeskCore\Library\Backup\DatabaseBackup;
use Alpdesk\AlpdeskCore\Library\Cryption\Cryption;
use Alpdesk\AlpdeskCore\Model\Database\AlpdeskcoreDatabasemanagerModel;
use Contao\BackendUser;
use Contao\CoreBundle\Controller\Backend\AbstractBackendController;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\Folder;
use Contao\StringUtil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class AlpdeskcoreDatabaseController extends AbstractBackendController
{
    private string $projectDir;
    private RequestStack $requestStack;
    private Security $security;

    public function __construct(
        string       $projectDir,
        RequestStack $requestStack,
        Security     $security
    )
    {
        $this->projectDir = $projectDir;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function endpoint(): Response
    {
        try {

            $backendUser = $this->security->getUser();

            if (!$backendUser instanceof BackendUser || !$backendUser->isAdmin) {
                throw new \Exception('BackendUser is not an admin user.');
            }

            $currentId = $this->requestStack->getCurrentRequest()->query->get('id');
            if ($currentId !== null && $currentId !== '') {
                $this->backupDatabaseSendBrowser((int)$currentId);
            }

        } catch (\Throwable $tr) {

            if ($tr instanceof ResponseException) {
                throw new ResponseException($tr->getResponse());
            }

        }

        return $this->render('@AlpdeskCore/alpdeskcore_database_error.html.twig', [
            'headline' => 'Error',
            'error' => 'error create backup'
        ]);

    }

    /**
     * @param int $currentId
     * @return void
     * @throws \Exception
     */
    private function backupDatabaseSendBrowser(int $currentId): void
    {
        $backup = new DatabaseBackup($this->projectDir);

        $currentObject = AlpdeskcoreDatabasemanagerModel::findByPk($currentId);
        if ($currentObject !== null) {

            $decryption = new Cryption(true);
            $password = $decryption->safeDecrypt((string)$currentObject->password);

            $title = StringUtil::generateAlias((string)$currentObject->title);

            $backup->setPrefix((new \DateTime())->format('Y-m-d-H_i_s') . '_');

            $backupFolder = new Folder('files/dbBackup');
            if ($backupFolder->isUnprotected()) {
                $backupFolder->protect();
            }

            $backup->backupDatabase((string)$currentObject->host, (string)$currentObject->username, $password, (string)$currentObject->database, $backupFolder->path, $title);

        }

        $backup->getBackupFile()?->sendToBrowser();

    }

}
