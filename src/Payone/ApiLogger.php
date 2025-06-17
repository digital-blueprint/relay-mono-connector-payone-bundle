<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Payone;

use OnlinePayments\Sdk\Logging\CommunicatorLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ApiLogger implements CommunicatorLogger, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param string $message
     */
    public function log($message): void
    {
        $this->logger->debug($message);
    }

    /**
     * @param string $message
     */
    public function logException($message, \Exception $exception): void
    {
        $this->logger->error($message, ['exception' => $exception]);
    }
}
