<?php

namespace Alpdesk\AlpdeskCore\Logging;

use Contao\Config;
use DateTimeZone;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Contao\CoreBundle\Framework\ContaoFramework;

class AlpdeskcoreLogger
{
    protected Logger $logger;
    protected ContaoFramework $framework;

    private bool $initialized;
    private string $rootDir;
    private string $environment;

    public function __construct(ContaoFramework $framework, string $rootDir, string $environment)
    {
        $this->framework = $framework;
        $this->initialized = false;
        $this->rootDir = $rootDir;
        $this->environment = $environment;
    }

    private function initialize(): void
    {
        if ($this->initialized === false) {

            $this->initialized = true;

            $this->framework->initialize();

            $this->logger = new Logger('alpdeskcorelogger');

            $timeZone = Config::get('timeZone');
            $this->logger->setTimezone(new DateTimeZone($timeZone));

            $handler = new RotatingFileHandler($this->rootDir . '/var/logs/' . $this->environment . '-alpdesk.log', 0, ($this->environment === 'dev' ? Logger::DEBUG : Logger::WARNING));

            $datimFormat = Config::get('datimFormat');
            if ($datimFormat === null || $datimFormat === '') {
                $datimFormat = 'd.m.Y H:i:s';
            }

            $handler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n", $datimFormat));

            $this->logger->pushHandler($handler);

        }

    }

    public function info(mixed $strText, mixed $strFunction): void
    {
        $this->initialize();
        $this->logger->info($strFunction . ' => ' . $strText);
    }

    public function debug(mixed $strText, mixed $strFunction): void
    {
        $this->initialize();
        $this->logger->debug($strFunction . ' => ' . $strText);
    }

    public function warning(mixed $strText, mixed $strFunction): void
    {
        $this->initialize();
        $this->logger->warning($strFunction . ' => ' . $strText);
    }

    public function error(mixed $strText, mixed $strFunction): void
    {
        $this->initialize();
        $this->logger->error($strFunction . ' => ' . $strText);
    }
}
