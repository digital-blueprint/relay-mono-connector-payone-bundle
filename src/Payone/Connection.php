<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Payone;

use OnlinePayments\Sdk\Authentication\V1HmacAuthenticator;
use OnlinePayments\Sdk\Client;
use OnlinePayments\Sdk\Communicator;
use OnlinePayments\Sdk\CommunicatorConfiguration;
use OnlinePayments\Sdk\Merchant\MerchantClient;
use OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Connection implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const INTEGRATOR = 'dbp/relay-mono-connector-payone-bundle';

    private $apiUrl;
    private $merchantId;
    private $apiKeyId;
    private $apiSecret;
    private ApiLogger $apiLogger;

    public function __construct(string $apiUrl, string $merchantId, string $apiKeyId, string $apiSecret, private bool $logging = false)
    {
        $this->apiUrl = $apiUrl;
        $this->merchantId = $merchantId;
        $this->apiKeyId = $apiKeyId;
        $this->apiSecret = $apiSecret;
        $this->logger = new NullLogger();
        $this->apiLogger = new ApiLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        $this->apiLogger->setLogger($this->logger);
    }

    public function getClient(): MerchantClient|MerchantClientInterface
    {
        $communicatorConfiguration = new CommunicatorConfiguration(
            $this->apiKeyId,
            $this->apiSecret,
            $this->apiUrl,
            self::INTEGRATOR,
        );
        $authenticator = new V1HmacAuthenticator($communicatorConfiguration);
        $communicator = new Communicator($communicatorConfiguration, $authenticator);
        if ($this->logging) {
            $communicator->enableLogging($this->apiLogger);
        }
        $client = new Client($communicator);
        $merchantClient = $client->merchant($this->merchantId);

        return $merchantClient;
    }
}
