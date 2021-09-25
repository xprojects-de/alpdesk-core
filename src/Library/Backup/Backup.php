<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Backup;

use Contao\Dbafs;
use Contao\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Backup
{

    private string $rootDir;
    private ?string $prefix = null;

    /**
     * @param string $rootDir
     */
    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @param string|null $prefix
     */
    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }


    /**
     * @param string $hostName
     * @param string $username
     * @param string|null $password
     * @param string $database
     * @param string $path
     * @param string $fileName
     * @param int $timeoutSeconds
     * @throws \Exception
     */
    public function backupDatabase(string $hostName, string $username, ?string $password, string $database, string $path, string $fileName, int $timeoutSeconds = 3600): void
    {
        try {

            $fileName .= '.sql';

            if ($this->getPrefix() !== null) {
                $fileName = $this->getPrefix() . $fileName;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $fileName;

            $executableFinder = new ExecutableFinder();
            $mySqlDump = $executableFinder->find('mysqldump', null, ['/Applications/MAMP/Library/bin']);

            if ($mySqlDump === null) {
                throw new \Exception('error finding mysqldump');
            }

            $parameters = [
                'HOSTNAME' => $hostName,
                'USERNAME' => $username,
                'DATABASE' => $database,
                'OUTPUT_FILE' => $this->rootDir . DIRECTORY_SEPARATOR . $fullPath
            ];

            $command = [
                $mySqlDump,
                '--add-drop-table',
                '--host="${:HOSTNAME}"',
                '--user="${:USERNAME}"'
            ];

            if ($password !== null && $password !== '') {

                $command[] = '--password="${:PASSWORD}"';
                $parameters['PASSWORD'] = $password;
            }

            $command[] = '"${:DATABASE}"';
            $command[] = '--result-file="${:OUTPUT_FILE}"';

            $process = Process::fromShellCommandline(\implode(' ', $command));

            try {

                $process->setTimeout($timeoutSeconds);
                $process->disableOutput();
                $process->run(null, $parameters);

                $fileObject = new File($fullPath);
                if (!$fileObject->exists()) {
                    throw new \Exception('error creating backup file');
                }

            } catch (ProcessFailedException $ex) {
                throw new \Exception($ex->getMessage());
            }

        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

    /**
     * @param string $path
     * @param string $fileName
     * @param array $exclude
     * @param array $additionalFiles
     * @param int $timeout
     * @throws \Exception
     */
    public function tarGzFileStructure(string $path, string $fileName, array $exclude, array $additionalFiles, int $timeout = 10000): void
    {
        try {

            // cd /tmp; tar --exclude=myFile.txt -czf backup.tar.gz * additional.txt;

            $options = ['cd', $path, ';', 'tar'];

            if (\count($exclude) > 0) {

                foreach ($exclude as $item) {
                    $options[] = '--exclude=' . $item;
                }

            }

            $options[] = '-czf';
            $options[] = $fileName . '.tar.gz';
            $options[] = '*';

            if (\count($additionalFiles) > 0) {

                foreach ($additionalFiles as $item) {
                    $options[] = $item;
                }

            }

            $process = new Process($options);

            try {

                $process->setTimeout($timeout);
                $process->disableOutput();
                $process->mustRun();

                $fileObject = new File($fileName);
                if (!$fileObject->exists()) {
                    throw new \Exception('error creating backup file');
                }

                if (Dbafs::shouldBeSynchronized($fileObject->path)) {
                    Dbafs::addResource($fileObject->path);
                }

            } catch (ProcessFailedException $ex) {
                throw new \Exception($ex->getMessage());
            }

        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }
    }

}