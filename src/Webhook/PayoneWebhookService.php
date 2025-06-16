<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Webhook;

use Dbp\Relay\MonoConnectorPayoneBundle\Config\PaymentContract;
use OnlinePayments\Sdk\Webhooks\InMemorySecretKeyStore;
use OnlinePayments\Sdk\Webhooks\WebhooksHelper;
use Symfony\Component\HttpFoundation\Request;

class PayoneWebhookService
{
    public function decryptRequest(PaymentContract $paymentContract, Request $request): WebhookRequest
    {
        $secretKeys = [];
        $secretKeys[$paymentContract->getWebhookId()] = $paymentContract->getWebhookSecret();
        $secretKeyStore = new InMemorySecretKeyStore($secretKeys);
        $webhooksHelper = new WebhooksHelper($secretKeyStore);
        $body = $request->getContent();
        $allRequestHeaders = $request->headers->all();
        $flattenedRequestHeaders = array_map(function ($requestHeaders) {
            return $requestHeaders[0];
        }, $allRequestHeaders);
        $event = $webhooksHelper->unmarshal($body, $flattenedRequestHeaders);

        $type = $event->type;
        $identifier = $event->getPayment()->getPaymentOutput()->getReferences()->getMerchantReference();

        $webhookRequest = new WebhookRequest(
            $type,
            $identifier,
            $event
        );

        return $webhookRequest;
    }
}
