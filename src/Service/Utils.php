<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Service;

class Utils
{
    public const PSP_ID = 'payone';

    /**
     * Extends the return URL passed from the client with extra data, so we can identify
     * the PSP data later on in extractCheckoutIdFromPspData().
     */
    public static function extendReturnUrl(string $returnUrl): string
    {
        if (substr($returnUrl, -1) !== '/') {
            $returnUrl .= '/';
        }
        $returnUrl .= self::PSP_ID;

        return $returnUrl;
    }

    /**
     * Returns true if this PSP connector is responsible for the passed PSP data string.
     */
    public static function isPspData(string $pspData): bool
    {
        $path = parse_url($pspData, PHP_URL_PATH);

        // we right-pad the extended URL with "/", so allow both
        return $path !== null && ($path === self::PSP_ID || $path === '/'.self::PSP_ID);
    }

    /**
     * Extracts the payment ID from the PSP data, which looks something like:
     *  "payone?RETURNMAC=<RETURNMAC>&hostedCheckoutId=<HOSTED_CHECKOUT_ID>"
     * Returns false if the PSP data format isn't known, or parsing failed.
     *
     * @return bool|string
     */
    public static function extractCheckoutIdFromPspData(string $pspData)
    {
        if (!self::isPspData($pspData)) {
            return false;
        }
        $query = parse_url($pspData, PHP_URL_QUERY);
        if ($query === null) {
            return false;
        }
        parse_str($query, $output);
        $returnMac = $output['RETURNMAC'] ?? null;
        $hostedCheckoutId = $output['hostedCheckoutId'] ?? null;
        if ($returnMac === null || $hostedCheckoutId === null) {
            return false;
        }

        return $hostedCheckoutId;
    }
}
