<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Webhook;

use Dbp\Relay\MonoBundle\Service\PaymentService;
use Dbp\Relay\MonoConnectorPayoneBundle\Config\ConfigurationService;
use Dbp\Relay\MonoConnectorPayoneBundle\Payone\Tools;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    private $auditLogger;

    public function __construct(
        ConfigurationService $configurationService,
        PaymentService $paymentService,
    ) {
        $this->configurationService = $configurationService;
        $this->paymentService = $paymentService;
        $this->logger = new NullLogger();
        $this->auditLogger = new NullLogger();
    }

    public function setAuditLogger(LoggerInterface $auditLogger): void
    {
        $this->auditLogger = $auditLogger;
    }

    public function index(Request $request, string $contract): Response
    {
        $paymentContract = $this->configurationService->getPaymentContractByIdentifier($contract);
        if ($paymentContract === null) {
            throw new BadRequestHttpException('Unknown contract: '.$contract);
        }

        $this->auditLogger->debug('payone: handling webhook for contract: '.$contract);
        $webhookRequest = WebhookRequest::validateRequest(
            $paymentContract->getWebhookId(), $paymentContract->getWebhookSecret(),
            $request
        );

        $json = $webhookRequest->getPayload()->toJson();
        $identifier = $webhookRequest->getIdentifier();
        $type = $webhookRequest->getType();
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->auditLogger->debug('payone: webhook request', ['type' => $type, 'identifier' => $identifier, 'data' => Tools::obfuscatePaymentData($data)]);

        if ($type === WebhookRequest::TYPE_CAPTURED) {
            $this->auditLogger->debug('payone: trying to complete payment', ['identifier' => $identifier]);
            $this->paymentService->completePayAction(
                $identifier
            );
        }

        return new JsonResponse();
    }
}
