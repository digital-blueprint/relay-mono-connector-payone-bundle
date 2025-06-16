<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Payone;

class PaymentList
{
    /**
     * @var ResultStatusCode
     */
    private $result;

    /**
     * @var PaymentData[]
     */
    private $payments;

    /**
     * @return PaymentData[]
     */
    public function getPayments(): array
    {
        return $this->payments;
    }

    /**
     * @param mixed[] $jsonResponse
     */
    public function fromJsonResponse(array $jsonResponse): void
    {
        $res = $jsonResponse['result'];
        $this->result = new ResultStatusCode($res['code'], $res['description']);

        $payments = [];
        foreach ($jsonResponse['payments'] as $item) {
            $payment = new PaymentData();
            $payment->fromJsonResponse($item);
            $payments[] = $payment;
        }

        $this->payments = $payments;
    }

    public function getResult(): ResultStatusCode
    {
        return $this->result;
    }
}
