<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\PayUnity;

class PaymentData
{
    /**
     * @var ResultCode
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
        $res = $jsonResponse['result'];
        $this->result = new ResultCode($res['code'], $res['description']);
        $this->id = $jsonResponse['id'] ?? null;
    }

    public function getResult(): ResultCode
    {
        return $this->result;
    }
}
