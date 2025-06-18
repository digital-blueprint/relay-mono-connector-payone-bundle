<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Payone;

use OnlinePayments\Sdk\Logging\BodyObfuscator;

class Tools
{
    public static function floatToAmount(float $amount): int
    {
        return (int) ($amount * 100);
    }

    /**
     * Partly obfuscates things like card expiry dates etc. Takes a response body as array.
     *
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public static function obfuscatePaymentData(array $data): array
    {
        // Use the SDK provided logging obfuscator for our own logging as well
        $obfuscator = new BodyObfuscator();

        return json_decode($obfuscator->obfuscateBody(
            BodyObfuscator::MIME_APPLICATION_JSON,
            json_encode($data, flags: JSON_THROW_ON_ERROR)), true, flags: JSON_THROW_ON_ERROR);
    }
}
