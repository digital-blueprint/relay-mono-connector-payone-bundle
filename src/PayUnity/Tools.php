<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\PayUnity;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;

class Tools
{
    public static function floatToAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    public static function createLoggerMiddleware(LoggerInterface $logger): callable
    {
        return Middleware::log(
            $logger,
            new MessageFormatter('[{method}] {uri}: CODE={code}, ERROR={error}, CACHE={res_header_X-Kevinrob-Cache}')
        );
    }
}
