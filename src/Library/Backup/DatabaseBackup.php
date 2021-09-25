<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Backup;

use Contao\Dbafs;
use Contao\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class DatabaseBackup
{

    private string $rootDir;
    private ?string $prefix = null;
    private ?File $backupFile = null;

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
     * @return File|null
     */
    public function getBackupFile(): ?File
    {
        return $this->backupFile;
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
            $mySqlDump = $executableFinder->find('mysqldump', null, ['/usr/bin', '/Applications/MAMP/Library/bin']);

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
                '--no-tablespaces',
                '--default-character-set=utf8mb4',
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
                $process->run(null, $parameters);

                $this->backupFile = new File($fullPath);

                $errorOutput = $process->getErrorOutput();
                if (\stripos($errorOutput, 'error') !== false) {

                    if ($this->backupFile->exists()) {

                        $this->backupFile->delete();
                        $this->backupFile = null;

                    }

                    throw new \Exception($errorOutput);
                }

                if (!$this->backupFile->exists()) {

                    $this->backupFile = null;
                    throw new \Exception('error creating backup file');

                }

                if (Dbafs::shouldBeSynchronized($this->backupFile->path)) {
                    Dbafs::addResource($this->backupFile->path);
                }

            } catch (ProcessFailedException $ex) {
                throw new \Exception($ex->getMessage());
            }

        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }

    }

}