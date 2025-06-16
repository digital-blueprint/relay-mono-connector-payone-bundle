<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Payone;

class Checkout
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $redirectUrl;

    public function getId(): string
    {
        return $this->id;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * @param mixed[] $jsonResponse
     */
    public function fromJsonResponse(array $jsonResponse): void
    {
        $this->id = $jsonResponse['hostedCheckoutId'];
        $this->redirectUrl = $jsonResponse['redirectUrl'];
    }
}
