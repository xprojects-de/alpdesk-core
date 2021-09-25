<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Backup;

use Contao\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Backup
{
    /**
     * @param string $hostName
     * @param string $username
     * @param string|null $password
     * @param string $database
     * @param string $fileName
     * @param int $timeout
     * @throws \Exception
     */
    public function backupDatabase(string $hostName, string $username, ?string $password, string $database, string $fileName, int $timeout = 10000): void
    {
        try {

            // mysqldump -h localhost -u myUsername -pMyPassword myDatabase > backup.sql'

            if ($password !== null && $password !== '') {
                $process = new Process(['mysqldump', '-h', $hostName, '-u', $username, '-p', $password, $database, '>', $fileName . '.sql']);
            } else {
                $process = new Process(['mysqldump', '-h', $hostName, '-u', $username, $database, '>', $fileName . '.sql']);
            }

            try {

                $process->setTimeout($timeout);
                $process->disableOutput();
                $process->mustRun();

                $fileObject = new File($fileName);
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

            } catch (ProcessFailedException $ex) {
                throw new \Exception($ex->getMessage());
            }

        } catch (\Throwable $tr) {
            throw new \Exception($tr->getMessage());
        }
    }

}