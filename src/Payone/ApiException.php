<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Payone;

class ApiException extends \Exception
{
    /**
     * @var ?ResultStatusCode
     */
    public $result;
}
