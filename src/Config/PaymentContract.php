<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Config;

class PaymentContract
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $merchantId;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiSecret;

    /**
     * @var ?string
     */
    private $webhookId;

    /**
     * @var ?string
     */
    private $webhookSecret;

    /**
     * @var array<string,PaymentMethod>
     */
    private $paymentMethods;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function setApiUrl(string $apiUrl): void
    {
        $this->apiUrl = $apiUrl;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function setMerchantId(string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getApiSecret(): string
    {
        return $this->apiSecret;
    }

    public function setApiSecret(string $apiSecret): void
    {
        $this->apiSecret = $apiSecret;
    }

    public function setWebhookId(?string $webhookId): void
    {
        $this->webhookId = $webhookId;
    }

    public function getWebhookId(): ?string
    {
        return $this->webhookId;
    }

    public function setWebhookSecret(?string $webhookSecret): void
    {
        $this->webhookSecret = $webhookSecret;
    }

    public function getWebhookSecret(): ?string
    {
        return $this->webhookSecret;
    }

    /**
     * @param array<string,PaymentMethod> $paymentMethods
     */
    public function setPaymentMethods(array $paymentMethods): void
    {
        $this->paymentMethods = $paymentMethods;
    }

    /**
     * @return array<string,PaymentMethod>
     */
    public function getPaymentMethods(): array
    {
        return $this->paymentMethods;
    }

    public function getPaymentMethod(string $methodId): ?PaymentMethod
    {
        return $this->paymentMethods[$methodId] ?? null;
    }

    /**
     * @param array<string,mixed> $config
     */
    public static function fromConfig(string $identifier, array $config): PaymentContract
    {
        $paymentContract = new PaymentContract();
        $paymentContract->setIdentifier($identifier);
        $paymentContract->setApiUrl($config['api_url']);
        $paymentContract->setMerchantId($config['merchant_id']);
        $paymentContract->setApiKey($config['api_key']);
        $paymentContract->setApiSecret($config['api_secret']);
        $paymentContract->setWebhookId($config['webhook_id']);
        $paymentContract->setWebhookSecret($config['webhook_secret']);
        $paymentMethods = [];
        foreach ($config['payment_methods'] as $id => $paymentMethodConfig) {
            $paymentMethod = new PaymentMethod();
            $paymentMethod->setIdentifier($id);
            $paymentMethod->setProducts($paymentMethodConfig['products']);
            $paymentMethods[$id] = $paymentMethod;
        }
        $paymentContract->setPaymentMethods($paymentMethods);

        return $paymentContract;
    }
}
