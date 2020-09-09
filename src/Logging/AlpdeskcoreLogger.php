<?php

namespace Alpdesk\AlpdeskCore\Logging;

use DateTimeZone;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Contao\CoreBundle\Framework\ContaoFramework;

class AlpdeskcoreLogger {

  protected Logger $logger;
  protected ContaoFramework $framework;

  public function __construct(ContaoFramework $framework, string $rootDir, string $environment) {
    $this->framework = $framework;
    $this->framework->initialize();
    $this->logger = new Logger('alpdeskcorelogger');
    $this->logger->setTimezone(new DateTimeZone($GLOBALS['TL_CONFIG']['timeZone']));
    $this->logger->pushHandler(new RotatingFileHandler($rootDir . '/var/logs/' . $environment . '-alpdesk.log', 0, ($environment == 'dev' ? Logger::DEBUG : Logger::WARNING)));
  }

  public function info($strText, $strFunction) {
    $this->logger->info($strFunction . ' => ' . $strText);
  }

  public function debug($strText, $strFunction) {
    $this->logger->debug($strFunction . ' => ' . $strText);
  }

  public function warning($strText, $strFunction) {
    $this->logger->warning($strFunction . ' => ' . $strText);
  }

  public function error($strText, $strFunction) {
    $this->logger->error($strFunction . ' => ' . $strText);
  }

}
