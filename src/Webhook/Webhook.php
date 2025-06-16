<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Webhook;

use Dbp\Relay\MonoBundle\Service\PaymentService;
use Dbp\Relay\MonoConnectorPayoneBundle\Config\ConfigurationService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Webhook extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var PayoneWebhookService
     */
    private $payoneWebhookService;

    public function __construct(
        ConfigurationService $configurationService,
        PaymentService $paymentService,
        PayoneWebhookService $payoneWebhookService
    ) {
        $this->configurationService = $configurationService;
        $this->paymentService = $paymentService;
        $this->payoneWebhookService = $payoneWebhookService;
        $this->logger = new NullLogger();
    }

    public function index(Request $request, string $contract): Response
    {
        $paymentContract = $this->configurationService->getPaymentContractByIdentifier($contract);
        if ($paymentContract === null) {
            throw new BadRequestHttpException('Unknown contract: '.$contract);
        }

        $webhookRequest = $this->payoneWebhookService->decryptRequest(
            $paymentContract,
            $request
        );

        $json = $webhookRequest->getPayload()->toJson();
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->logger->debug('payone: webhook request', ['data' => $data]);

        if ($webhookRequest->getType() === WebhookRequest::TYPE_CAPTURED) {
            $identifier = $webhookRequest->getIdentifier();
            $this->paymentService->completePayAction(
                $identifier
            );
        }

        return new JsonResponse();
    }
}
