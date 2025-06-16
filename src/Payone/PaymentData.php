<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Payone;

class PaymentData
{
    /**
     * @var ResultStatusCode
     */
    private $result;

    /**
     * @var ?string
     */
    private $id;

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param mixed[] $jsonResponse
     */
    public function fromJsonResponse(array $jsonResponse): void
    {
        $res = $jsonResponse['payment'];
        $this->result = new ResultStatusCode($res['statusOutput']['statusCode'], $res['status']);
        $this->id = $res['id'] ?? null;
    }

    public function getResult(): ResultStatusCode
    {
        return $this->result;
    }
}
