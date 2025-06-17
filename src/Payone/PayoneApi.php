<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Payone;

use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use Dbp\Relay\MonoConnectorPayoneBundle\Service\Utils;
use OnlinePayments\Sdk\Domain\AmountOfMoney;
use OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use OnlinePayments\Sdk\Domain\GetPaymentProductsResponse;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\Order;
use OnlinePayments\Sdk\Domain\OrderReferences;
use OnlinePayments\Sdk\Domain\PaymentProductFilter;
use OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckout;
use OnlinePayments\Sdk\Domain\ValidateCredentialsRequest;
use OnlinePayments\Sdk\Merchant\Products\GetPaymentProductsParams;
use OnlinePayments\Sdk\ResponseException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PayoneApi implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var LoggerInterface
     */
    private $auditLogger;

    /**
     * @var mixed[]
     */
    private $loggingContext;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->logger = new NullLogger();
        $this->auditLogger = new NullLogger();
        $this->loggingContext = [];
    }

    public function setAuditLogger(LoggerInterface $auditLogger): void
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * @param mixed[] $loggingContext
     */
    public function setLoggingContext(array $loggingContext): void
    {
        $this->loggingContext = $loggingContext;
    }

    /**
     * @param mixed[] $context
     *
     * @return mixed[]
     */
    private function withLoggingContext(array $context = []): array
    {
        return array_merge($this->loggingContext, $context);
    }

    /**
     * Prepare a checkout.
     *
     * @param PaymentPersistence $payment            - Payment
     * @param                    $amount             - Indicates the amount of the payment request. The dot is used as decimal separator.
     * @param                    $currency           - The currency code of the payment request's amount (ISO 4217)
     * @param array<string>      $restrictToProducts - Restrict the payment products to the given list
     */
    public function prepareCheckout(PaymentPersistence $payment, int $amount, string $currency, array $restrictToProducts): Checkout
    {
        $client = $this->connection->getClient();

        $createHostedCheckoutRequest = new CreateHostedCheckoutRequest();

        $cardPaymentMethodSpecificInput = new CardPaymentMethodSpecificInput();
        $cardPaymentMethodSpecificInput->setAuthorizationMode(AuthorizationMode::SALE);
        $createHostedCheckoutRequest->setCardPaymentMethodSpecificInput($cardPaymentMethodSpecificInput);

        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setAmount($amount);
        $amountOfMoney->setCurrencyCode($currency);

        $references = new OrderReferences();
        $references->setMerchantReference($payment->getIdentifier());

        $order = new Order();
        $order->setAmountOfMoney($amountOfMoney);
        $order->setReferences($references);

        $hostedCheckoutSpecificInput = new HostedCheckoutSpecificInput();
        $returnUrl = Utils::extendReturnUrl($payment->getPspReturnUrl());
        $hostedCheckoutSpecificInput->setReturnUrl($returnUrl);

        if (count($restrictToProducts) > 0) {
            $paymentProductsQuery = new GetPaymentProductsParams();
            $paymentProductsQuery->setCountryCode('AT');
            $paymentProductsQuery->setCurrencyCode($currency);
            /** @var GetPaymentProductsResponse $paymentProductsResponse */
            $paymentProductsResponse = $client->products()->getPaymentProducts($paymentProductsQuery);
            $paymentProducts = $paymentProductsResponse->getPaymentProducts();
            $products = [];
            foreach ($paymentProducts as $paymentProduct) {
                if (in_array($paymentProduct->getDisplayHints()->getLabel(), $restrictToProducts, true)) {
                    $products[] = $paymentProduct->getId();
                }
            }
            $paymentProductFilters = new PaymentProductFiltersHostedCheckout();
            $paymentProductFilter = new PaymentProductFilter();
            $paymentProductFilter->setProducts($products);
            $paymentProductFilters->setRestrictTo($paymentProductFilter);
            $hostedCheckoutSpecificInput->setPaymentProductFilters($paymentProductFilters);
        }

        $createHostedCheckoutRequest->setOrder($order);
        $createHostedCheckoutRequest->setHostedCheckoutSpecificInput($hostedCheckoutSpecificInput);

        try {
            $response = $client->hostedCheckout()->createHostedCheckout($createHostedCheckoutRequest);
        } catch (\Exception $e) {
            throw self::createResponseError($e);
        }

        $checkout = $this->parseCreateHostedCheckoutResponse($response);

        return $checkout;
    }

    public function testConnection(): void
    {
        $client = $this->connection->getClient();
        $client->services()->testConnection();
    }

    public function testWebhookCredentials(string $webhookId, string $webhookSecret): void
    {
        $client = $this->connection->getClient();
        $request = new ValidateCredentialsRequest();
        $request->setKey($webhookId);
        $request->setSecret(base64_encode(hash_hmac('sha256', '', $webhookSecret, true)));
        $response = $client->webhooks()->validateWebhookCredentials($request);
        if ($response->getResult() !== 'Valid') {
            throw new \RuntimeException('Invalid webhook credentials. Result: '.$response->getResult());
        }
    }

    /**
     * Get the payment status.
     *
     * Once a status response is successful the checkout identifier can't be used anymore.
     * A throttling rule applies for get payment status calls. Per checkout,
     * it is allowed to send two get payment requests in a minute.
     */
    public function getPaymentStatus(string $checkoutId): PaymentData
    {
        $client = $this->connection->getClient();

        $this->auditLogger->debug('payone: get payment status', $this->withLoggingContext(['checkoutId' => $checkoutId]));

        try {
            $response = $client->hostedCheckout()->getHostedCheckout($checkoutId);
        } catch (\Exception $e) {
            throw self::createResponseError($e);
        }

        $paymentData = $this->parseGetHostedCheckoutResponse($response);

        return $paymentData;
    }

    public function capturePayment(string $paymentId, int $amount): bool
    {
        $client = $this->connection->getClient();

        $capturePaymentRequest = new CapturePaymentRequest();
        $capturePaymentRequest->setAmount($amount);

        $this->auditLogger->debug('payone: capture payment', $this->withLoggingContext(['paymentId' => $paymentId]));

        try {
            $client->payments()->capturePayment($paymentId, $capturePaymentRequest);

            return true;
        } catch (\Exception $e) {
            throw self::createResponseError($e);
        }
    }

    private function parseCreateHostedCheckoutResponse(CreateHostedCheckoutResponse $response): Checkout
    {
        $json = $response->toJson();
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->auditLogger->debug('payone: create hosted checkout response', $this->withLoggingContext(['data' => $data]));

        $checkout = new Checkout();
        $checkout->fromJsonResponse($data);

        return $checkout;
    }

    private function parseGetHostedCheckoutResponse(GetHostedCheckoutResponse $response): PaymentData
    {
        $json = $response->toJson();
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->auditLogger->debug('payone: get hosted checkout response', $this->withLoggingContext(['data' => $data]));

        $paymentData = new PaymentData();
        $paymentData->fromJsonResponse($data['createdPaymentOutput']);

        return $paymentData;
    }

    private function createResponseError(\Exception $e): ApiException
    {
        if ($e instanceof ResponseException) {
            $response = $e->getResponse();
            $json = $response->toJson();
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $this->auditLogger->error('payone: parse error response', $this->withLoggingContext(['data' => $data]));
            $result = $data['result'];
            $code = $result['code'];
            $description = $result['description'];
            $message = "[$code] $description";
            $exc = new ApiException($message);
            $exc->result = new ResultStatusCode($code, $description);

            return $exc;
        }

        return new ApiException('Unknown error');
    }
}
