<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Payone;

class Tools
{
    public static function floatToAmount(float $amount): int
    {
        return (int) ($amount * 100);
    }
}
