<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Service;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Locale\Locale;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use Dbp\Relay\MonoBundle\Persistence\PaymentStatus;
use Dbp\Relay\MonoConnectorPayoneBundle\Config\ConfigurationService;
use Dbp\Relay\MonoConnectorPayoneBundle\Config\PaymentContract;
use Dbp\Relay\MonoConnectorPayoneBundle\Payone\ApiException;
use Dbp\Relay\MonoConnectorPayoneBundle\Payone\Checkout;
use Dbp\Relay\MonoConnectorPayoneBundle\Payone\Connection;
use Dbp\Relay\MonoConnectorPayoneBundle\Payone\PaymentData;
use Dbp\Relay\MonoConnectorPayoneBundle\Payone\PayoneApi;
use Dbp\Relay\MonoConnectorPayoneBundle\Payone\ResultStatusCode;
use Dbp\Relay\MonoConnectorPayoneBundle\Payone\Tools;
use Dbp\Relay\MonoConnectorPayoneBundle\Persistence\PaymentDataService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

class PayoneService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Connection[]
     */
    private $connection = [];

    /**
     * @var PaymentDataService
     */
    private $paymentDataService;

    /**
     * @var LockFactory
     */
    private $lockFactory;

    /**
     * @var LoggerInterface
     */
    private $auditLogger;
    /**
     * @var ConfigurationService
     */
    private $configService;

    public function __construct(
        PaymentDataService $paymentDataService,
        LockFactory $lockFactory,
        ConfigurationService $configService,
        private Locale $locale,
    ) {
        $this->paymentDataService = $paymentDataService;
        $this->lockFactory = $lockFactory;
        $this->logger = new NullLogger();
        $this->auditLogger = new NullLogger();
        $this->configService = $configService;
    }

    public function setAuditLogger(LoggerInterface $auditLogger): void
    {
        $this->auditLogger = $auditLogger;
    }

    private function createPaymentLock(PaymentPersistence $payment): LockInterface
    {
        $resourceKey = sprintf(
            'mono-connector-payone-%s',
            $payment->getIdentifier()
        );

        return $this->lockFactory->createLock($resourceKey, 60, true);
    }

    /**
     * @return PaymentContract[]
     */
    public function getContracts(): array
    {
        return $this->configService->getPaymentContracts();
    }

    public function getPaymentIdForPspData(string $pspData): ?string
    {
        // First check if the PSP data is for us, null means we don't handle it
        if (!Utils::isPspData($pspData)) {
            return null;
        }

        // Then extract the checkoutId
        $checkoutId = Utils::extractCheckoutIdFromPspData($pspData);
        if ($checkoutId === false) {
            throw new ApiError(Response::HTTP_BAD_REQUEST, 'Invalid PSP data');
        }

        $paymentDataPersistence = $this->paymentDataService->getByCheckoutId($checkoutId);
        if (!$paymentDataPersistence) {
            throw ApiError::withDetails(Response::HTTP_NOT_FOUND, 'Payment data was not found!', 'mono:payment-data-not-found');
        }

        // Then map it to the payment ID and return that to the mono bundle
        return $paymentDataPersistence->getPaymentIdentifier();
    }

    public function checkConnection(string $contract): void
    {
        $api = $this->getApiByContract($contract, null);
        $api->testConnection();

        $contractConfig = $this->configService->getPaymentContractByIdentifier($contract);
        $api->testWebhookCredentials($contractConfig->getWebhookId(), $contractConfig->getWebhookSecret());
    }

    /**
     * @return mixed[]
     */
    public function getLoggingContext(PaymentPersistence $payment): array
    {
        return ['relay-mono-payment-id' => $payment->getIdentifier()];
    }

    public function getApiByContract(string $contractId, ?PaymentPersistence $payment): PayoneApi
    {
        if (!array_key_exists($contractId, $this->connection)) {
            $contract = $this->getPaymentContractByIdentifier($contractId);
            $this->connection[$contractId] = new Connection(
                $contract->getApiUrl(),
                $contract->getMerchantId(),
                $contract->getApiKeyId(),
                $contract->getApiSecret(),
            );
        }

        $connection = $this->connection[$contractId];
        $connection->setLogger($this->logger);
        $api = new PayoneApi($connection);
        $api->setLogger($this->logger);
        $api->setAuditLogger($this->auditLogger);
        if ($payment !== null) {
            $api->setLoggingContext($this->getLoggingContext($payment));
        }

        return $api;
    }

    /**
     * @param array<string,mixed> $restrictToProducts
     */
    public function prepareCheckout(PaymentPersistence $payment, string $pspContract, string $pspMethod, int $amount, string $currency, array $restrictToProducts, ?string $variant): Checkout
    {
        $existingPaymentData = $this->paymentDataService->getByPaymentIdentifier($payment->getIdentifier());
        if ($existingPaymentData !== null) {
            $this->auditLogger->error('payone: Can\'t create a new checkout, there already exists one', $this->getLoggingContext($payment));
            throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR, 'Checkout can\'t be created');
        }

        $api = $this->getApiByContract($pspContract, $payment);

        try {
            $checkout = $api->prepareCheckout($payment, $amount, $currency, $restrictToProducts, $this->locale->getCurrentPrimaryLanguage(), $variant);
        } catch (ApiException $e) {
            $this->logger->error('Communication error with payment service provider!', ['exception' => $e]);
            throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR, 'Communication error with payment service provider!');
        }

        try {
            $this->paymentDataService->createPaymentData($pspContract, $pspMethod, $payment, $checkout);
        } catch (\Exception $e) {
            $this->logger->error('Payment data could not be created!', ['exception' => $e]);
            throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment data could not be created!');
        }

        return $checkout;
    }

    private function getPaymentContractByIdentifier(string $contractId): PaymentContract
    {
        $contract = $this->configService->getPaymentContractByIdentifier($contractId);
        if ($contract === null) {
            throw new \RuntimeException("Contract $contractId doesn't exist");
        }

        return $contract;
    }

    public function startPayment(string $pspContract, string $pspMethod, PaymentPersistence $payment): string
    {
        $contract = $this->getPaymentContractByIdentifier($pspContract);
        $amount = Tools::floatToAmount((float) $payment->getAmount());
        $currency = $payment->getCurrency();
        $paymentMethods = $contract->getPaymentMethod($pspMethod);
        $restrictToProducts = $paymentMethods->getProducts();
        $templateVariant = $paymentMethods->getTemplateVariant();

        $lock = $this->createPaymentLock($payment);
        $lock->acquire(true);
        try {
            $checkoutData = $this->prepareCheckout($payment, $pspContract, $pspMethod, $amount, $currency, $restrictToProducts, $templateVariant);
            // TODO: get the real status
            $payment->setPaymentStatus(PaymentStatus::PENDING);
        } finally {
            $lock->release();
        }

        return $checkoutData->getRedirectUrl();
    }

    public function setPaymentStatusForResult(PaymentPersistence $payment, ResultStatusCode $result): void
    {
        if ($result->isSuccessfullyProcessed() || $result->isSuccessfullyProcessedNeedsManualReview()) {
            $this->auditLogger->debug('payone: Setting payment to complete', $this->getLoggingContext($payment));
            $payment->setPaymentStatus(PaymentStatus::COMPLETED);
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $payment->setCompletedAt($now);
        } elseif ($result->isPending() || $result->isPendingExtra()) {
            $this->auditLogger->debug('payone: Setting payment to pending', $this->getLoggingContext($payment));
            $payment->setPaymentStatus(PaymentStatus::PENDING);
        } else {
            $this->auditLogger->debug('payone: Setting payment to failed', $this->getLoggingContext($payment));
            $payment->setPaymentStatus(PaymentStatus::FAILED);
        }
    }

    public function updatePaymentStatus(string $pspContract, PaymentPersistence $payment): void
    {
        $lock = $this->createPaymentLock($payment);
        $lock->acquire(true);

        $this->auditLogger->debug('payone: Checking if payment is completed', $this->getLoggingContext($payment));

        try {
            $paymentDataPersistence = $this->paymentDataService->getByPaymentIdentifier($payment->getIdentifier());
            if ($paymentDataPersistence === null) {
                throw ApiError::withDetails(Response::HTTP_NOT_FOUND, 'Payment data was not found!', 'mono:payment-data-not-found');
            }

            if ($payment->getPaymentStatus() === PaymentStatus::COMPLETED) {
                $this->auditLogger->debug('payone: payment already completed, nothing to do', $this->getLoggingContext($payment));
            } else {
                $pspIdentifier = $paymentDataPersistence->getPspIdentifier();
                $this->auditLogger->debug('payone: Found existing checkout: '.$pspIdentifier, $this->getLoggingContext($payment));
                $paymentData = $this->getCheckoutPaymentData($payment, $pspContract, $pspIdentifier);

                // capture should not be necessary because of authorization mode set to 'SALE' during payment initalization
                if ($paymentData->getResult()->isCapturable()) {
                    $this->auditLogger->debug('payone: payment is capturable', $this->getLoggingContext($payment));
                    $api = $this->getApiByContract($pspContract, $payment);
                    $paymentId = $paymentData->getId();
                    $amount = Tools::floatToAmount((float) $payment->getAmount());
                    $api->capturePayment($paymentId, $amount);
                    $paymentData = $this->getCheckoutPaymentData($payment, $pspContract, $pspIdentifier);
                }

                $result = $paymentData->getResult();
                $this->setPaymentStatusForResult($payment, $result);
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Delete everything related to the passed payment in the connector.
     */
    public function cleanupPaymentData(PaymentPersistence $payment): void
    {
        $this->auditLogger->debug('payone: clean up payment data', $this->getLoggingContext($payment));
        $this->paymentDataService->cleanupByPaymentIdentifier($payment->getIdentifier());
    }

    private function getCheckoutPaymentData(PaymentPersistence $payment, string $pspContract, string $checkoutId): PaymentData
    {
        $api = $this->getApiByContract($pspContract, $payment);
        try {
            return $api->getPaymentStatus($checkoutId);
        } catch (ApiException $e) {
            $this->logger->error('Communication error with payment service provider!', ['exception' => $e]);
            throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR, 'Communication error with payment service provider!');
        }
    }
}
