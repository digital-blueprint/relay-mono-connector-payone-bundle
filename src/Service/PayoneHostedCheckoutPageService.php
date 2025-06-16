<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Service;

use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponse;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\CompleteResponseInterface;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\PaymentServiceProviderServiceInterface;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\StartResponse;
use Dbp\Relay\MonoBundle\PaymentServiceProvider\StartResponseInterface;
use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class PayoneHostedCheckoutPageService implements PaymentServiceProviderServiceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var PayoneService
     */
    private $payoneService;

    public function __construct(PayoneService $payoneService)
    {
        $this->payoneService = $payoneService;
    }

    public function start(string $pspContract, string $pspMethod, PaymentPersistence $paymentPersistence): StartResponseInterface
    {
        $widgetUrl = $this->payoneService->startPayment($pspContract, $pspMethod, $paymentPersistence);
        $data = null;
        $error = null;

        return new StartResponse(
            $widgetUrl,
            $data,
            $error
        );
    }

    public function getPaymentIdForPspData(string $pspData): ?string
    {
        return $this->payoneService->getPaymentIdForPspData($pspData);
    }

    public function complete(string $pspContract, PaymentPersistence $paymentPersistence): CompleteResponseInterface
    {
        $this->payoneService->updatePaymentStatus($pspContract, $paymentPersistence);

        return new CompleteResponse($paymentPersistence->getReturnUrl());
    }

    public function cleanup(string $pspContract, PaymentPersistence $paymentPersistence): bool
    {
        $this->payoneService->cleanupPaymentData($paymentPersistence);

        return true;
    }

    public function getPspContracts(): array
    {
        $ids = [];
        foreach ($this->payoneService->getContracts() as $contract) {
            $ids[] = $contract->getIdentifier();
        }

        return $ids;
    }

    public function getPspMethods(string $pspContract): array
    {
        foreach ($this->payoneService->getContracts() as $contract) {
            if ($contract->getIdentifier() === $pspContract) {
                return array_keys($contract->getPaymentMethods());
            }
        }

        return [];
    }
}
