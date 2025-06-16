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
use Psr\Log\NullLogger;

class Connection implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const INTEGRATOR = 'TU Graz';

    private $apiUrl;
    private $merchantId;
    private $apiKey;
    private $apiSecret;

    public function __construct(string $apiUrl, string $merchantId, string $apiKey, string $apiSecret)
    {
        $this->apiUrl = $apiUrl;
        $this->merchantId = $merchantId;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->logger = new NullLogger();
    }

    public function getClient(): MerchantClient|MerchantClientInterface
    {
        $communicatorConfiguration = new CommunicatorConfiguration(
            $this->apiKey,
            $this->apiSecret,
            $this->apiUrl,
            self::INTEGRATOR,
        );
        $authenticator = new V1HmacAuthenticator($communicatorConfiguration);
        $communicator = new Communicator($communicatorConfiguration, $authenticator);
        $client = new Client($communicator);
        $merchantClient = $client->merchant($this->merchantId);

        return $merchantClient;
    }
}
