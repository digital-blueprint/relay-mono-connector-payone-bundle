<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Config;

class ConfigurationService
{
    /**
     * @var mixed[]
     */
    private $config = [];

    /**
     * @param mixed[] $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getPaymentContractByIdentifier(string $identifier): ?PaymentContract
    {
        $paymentContract = null;

        if (array_key_exists($identifier, $this->config['payment_contracts'])) {
            $paymentContractConfig = $this->config['payment_contracts'][$identifier];
            $paymentContract = PaymentContract::fromConfig($identifier, $paymentContractConfig);
        }

        return $paymentContract;
    }

    /**
     * @return PaymentContract[]
     */
    public function getPaymentContracts(): array
    {
        $contracts = [];
        $config = $this->config['payment_contracts'] ?? [];
        foreach ($config as $identifier => $paymentContractConfig) {
            $contracts[] = PaymentContract::fromConfig($identifier, $paymentContractConfig);
        }

        return $contracts;
    }
}
