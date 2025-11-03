<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Storage;

use Contao\Environment;
use Contao\StringUtil;
use Contao\Validator;
use Symfony\Component\Filesystem\Filesystem;

abstract class BaseStorage
{
    protected string $rootDir;

    /**
     * @param string $rootDir
     */
    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    abstract public function initialize(?array $config): void;

    abstract public function findByUuid(mixed $strUuid): ?StorageObject;

    abstract public function deleteByUuid(mixed $strUuid): void;

    abstract public function existsByUuid(mixed $strUuid): bool;

    abstract public function uuidForDb(mixed $uuid, bool $checkFileExists): ?string;

    abstract public function dbToUuid(?string $bin, bool $checkFileExists): ?string;

    abstract public function deploy(?string $localPath, ?string $remotePath, bool $override): ?StorageObject;

    abstract public function synchronize(mixed $strUuid): void;

    abstract public function createFile(string $filePath, mixed $content): ?StorageObject;

    abstract public function createDirectory(string $filePath): ?StorageObject;

    abstract public function setMeta(StorageObject $object): void;

    abstract public function rename(string $srcPath, string $destFileName): ?StorageObject;

    /**
     * @param string|null $bin
     * @return string|null
     */
    public static function binToUuid(?string $bin): ?string
    {
        try {

            if ($bin === null || $bin === '') {
                return null;
            }

            if (Validator::isStringUuid($bin) === true) {
                return $bin;
            }

            $uuid = StringUtil::binToUuid($bin);

            if (Validator::isStringUuid($uuid) === false) {
                return null;
            }

            return $uuid;

        } catch (\Exception) {
        }

        return null;

    }

    /**
     * @return string
     */
    public function getPublicDir(): string
    {
        if ((new Filesystem())->exists($this->rootDir . '/web')) {
            return 'web';
        }

        return 'public';
    }

    /**
     * @param string|null $path
     * @return string|null
     */
    public function generateLocalUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $publicDir = $this->getPublicDir() . '/';
        if (\str_starts_with($path, $publicDir)) {
            $path = \substr($path, \strlen($publicDir));
        }

        return Environment::get('base') . $path;

    }

    /**
     * @param string $path
     * @return string[]
     */
    public function getPathInfo(string $path): array
    {
        $matches = array();
        $return = array('dirname' => '', 'basename' => '', 'extension' => '', 'filename' => '');

        \preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^.\\\\/]+?)|))[\\\\/.]*$%m', $path, $matches);

        if (isset($matches[1])) {
            $return['dirname'] = $this->rootDir . '/' . $matches[1]; // see #8325
        }

        if (isset($matches[2])) {
            $return['basename'] = $matches[2];
        }

        if (isset($matches[5])) {
            $return['extension'] = $matches[5];
        }

        if (isset($matches[3])) {
            $return['filename'] = $matches[3];
        }

        return $return;
    }

}