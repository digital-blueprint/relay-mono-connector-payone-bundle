<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Webhook;

use OnlinePayments\Sdk\Domain\WebhooksEvent;

class WebhookRequest
{
    /**
     * The transaction has been captured and we have received online confirmation.
     */
    public const TYPE_CAPTURED = 'payment.captured';

    /**
     * Type of the notification.
     *
     * @var string
     */
    private $type;

    /**
     * Payment Identifier.
     *
     * @var ?string
     */
    private $identifier;

    /**
     * Content of the notification.
     * If the notification type is payment or registration, the payload's content will be identical
     * to the response you received on the payment or registration.
     *
     * @var WebhooksEvent
     */
    private $payload;

    public function __construct(string $type, ?string $identifier, WebhooksEvent $payload)
    {
        $this->type = $type;
        $this->identifier = $identifier;
        $this->payload = $payload;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setPayload(WebhooksEvent $payload): void
    {
        $this->payload = $payload;
    }

    public function getPayload(): WebhooksEvent
    {
        return $this->payload;
    }
}
