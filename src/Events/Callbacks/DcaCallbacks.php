<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Callbacks;

use Alpdesk\AlpdeskCore\Library\Backup\DatabaseBackup;
use Alpdesk\AlpdeskCore\Library\Cryption\Cryption;
use Alpdesk\AlpdeskCore\Model\Database\AlpdeskcoreDatabasemanagerModel;
use Contao\Controller;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\DataContainer;
use Contao\Folder;
use Contao\Image;
use Alpdesk\AlpdeskCore\Events\AlpdeskCoreEventService;
use Alpdesk\AlpdeskCore\Events\Event\AlpdeskCoreRegisterPlugin;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DcaCallbacks
{
    protected AlpdeskCoreEventService $eventService;
    protected ?RequestStack $requestStack;
    protected string $rootDir;
    protected ?LoggerInterface $logger;
    private Connection $connection;

    /**
     * @param AlpdeskCoreEventService $eventService
     * @param RequestStack $requestStack
     * @param string $rootDir
     * @param Connection $connection
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        AlpdeskCoreEventService $eventService,
        RequestStack            $requestStack,
        string                  $rootDir,
        Connection              $connection,
        LoggerInterface         $logger = null
    )
    {
        $this->eventService = $eventService;
        $this->requestStack = $requestStack;
        $this->rootDir = $rootDir;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    private function getLegacyElements(): array
    {
        $data = [];

        if (isset($GLOBALS['TL_ADME']) && \count($GLOBALS['TL_ADME'])) {
            foreach ($GLOBALS['TL_ADME'] as $k => $v) {
                $data[$k] = $GLOBALS['TL_LANG']['ADME'][$k];
            }
        }

        return $data;
    }

    /**
     * @param DataContainer|null $dc
     * @return array
     */
    public function getMandantElements(?DataContainer $dc): array
    {
        $event = new AlpdeskCoreRegisterPlugin($this->getLegacyElements(), []);
        $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreRegisterPlugin::NAME);

        return $event->getPluginData();
    }

    /**
     * @param array $arrRow
     * @return string
     */
    public function addMandantElementType(array $arrRow): string
    {
        $event = new AlpdeskCoreRegisterPlugin($this->getLegacyElements(), []);
        $this->eventService->getDispatcher()->dispatch($event, AlpdeskCoreRegisterPlugin::NAME);

        $dataComplete = $event->getPluginData();

        $key = $arrRow['disabled'] ? 'unpublished' : 'published';
        $icon = (($arrRow['invisible'] | $arrRow['disabled']) ? 'invisible.svg' : 'visible.svg');
        $type = $dataComplete[$arrRow['type']] ?: '- INVALID -';

        return '<div class="cte_type ' . $key . '">' . Image::getHtml($icon) . '&nbsp;&nbsp;' . $type . '</div>';
    }

    /**
     * @param DataContainer|null $dc
     */
    public function databaseManagerOnLoad(DataContainer $dc = null): void
    {
        $GLOBALS['TL_CSS'][] = 'bundles/alpdeskcore/css/alpdeskcore_widget_databasemanager.css';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/alpdeskcore/js/alpdeskcore_widget_databasemanager.js';

        $act = $this->requestStack->getCurrentRequest()->query->get('act');
        if ($act === 'backup') {

            $currentId = $this->requestStack->getCurrentRequest()->query->get('id');
            if ($currentId !== null && $currentId !== '') {

                $backup = new DatabaseBackup($this->rootDir);

                try {

                    $currentObject = AlpdeskcoreDatabasemanagerModel::findByPk((int)$currentId);
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

                } catch (\Exception $ex) {

                    $this->logger?->error(
                        $ex->getMessage(),
                        ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, '')]
                    );

                }

                if ($backup->getBackupFile() !== null) {

                    $this->logger?->info(
                        $backup->getBackupFile()->name . ' created',
                        ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, '')]
                    );

                    $backup->getBackupFile()->sendToBrowser();

                }

            }

            Controller::redirect('/contao?do=alpdeskcore_databasemanager');

        }

    }

    /**
     * @param DataContainer|null $dc
     * @return array
     */
    public function getCrudTables(?DataContainer $dc): array
    {
        try {

            $schemaManager = $this->connection->createSchemaManager();
            $tables = $schemaManager->createSchema()->getTables();

            $preparedTables = [];
            foreach ($tables as $table) {

                if ($table instanceof Table) {
                    $preparedTables[$table->getName()] = $table->getName();
                }

            }

            return $preparedTables;


        } catch (\Throwable $tr) {
            return [];
        }

    }

}
