<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\PayUnity;

class ApiException extends \Exception
{
    /**
     * @var ?ResultCode
     */
    public $result;
}
