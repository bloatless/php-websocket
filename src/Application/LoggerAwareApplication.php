<?php
declare(strict_types=1);

namespace Bloatless\WebSocket\Application;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class LoggerAwareApplication extends Application implements LoggerAwareInterface {
    private LoggerInterface $logger;

    protected function __construct()
    {
        parent::__construct();

        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
