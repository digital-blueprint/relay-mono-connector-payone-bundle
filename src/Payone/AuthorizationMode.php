<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Payone;

/**
 * Authorization mode.
 */
class AuthorizationMode
{
    /**
     * The payment creation results in an authorization that is ready for capture. Final authorizations can't be
     * reversed and need to be captured for the full amount within 7 days.
     */
    public const FINAL_AUTHORIZATION = 'FINAL_AUTHORIZATION';

    /**
     * The payment creation results in a pre-authorization that is ready for capture. Pre-authortizations can be
     * reversed and can be captured within 30 days. The capture amount can be lower than the authorized amount.
     */
    public const PRE_AUTHORIZATION = 'PRE_AUTHORIZATION';

    /**
     * The payment creation results in an authorization that is already captured at the moment of approval.
     */
    public const SALE = 'SALE';
}
