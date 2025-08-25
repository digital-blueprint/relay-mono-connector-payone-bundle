<?php

declare(strict_types=1);

use Dbp\Relay\MonoConnectorPayoneBundle\Payone\PaymentData;
use Dbp\Relay\MonoConnectorPayoneBundle\Payone\Tools;
use Dbp\Relay\MonoConnectorPayoneBundle\Service\Utils;
use PHPUnit\Framework\TestCase;

class PayoneTest extends TestCase
{
    public function testObfuscatePaymentData()
    {
        $body = '
   {
       "card": {
            "bin": "43302649",
            "cardNumber": "************4675",
            "countryCode": "BE",
            "expiryDate": "0129"
        }
   }';

        $output = Tools::obfuscatePaymentData(json_decode($body, true));
        $this->assertSame('433026**', $output['card']['bin']);
        $this->assertSame('************4675', $output['card']['cardNumber']);
        $this->assertSame('**29', $output['card']['expiryDate']);
        $this->assertSame('BE', $output['card']['countryCode']);
    }

    public function testParsePaymentData()
    {
        // From a payment using the PAYYONE test credit card data on a test account
        $body = '{
    "apiVersion": "v1",
    "created": "2025-06-18T10:56:29.1276889+02:00",
    "id": "fb7ce370-5920-4ece-a44a-3f46e4bff20d",
    "merchantId": "Something",
    "type": "payment.captured",
    "payment": {
        "id": "4361536049_0",
        "paymentOutput": {
            "acquiredAmount": {
                "amount": 2020,
                "currencyCode": "EUR"
            },
            "amountOfMoney": {
                "amount": 2020,
                "currencyCode": "EUR"
            },
            "cardPaymentMethodSpecificOutput": {
                "acquirerInformation": {
                    "name": "ACQUIRER"
                },
                "authorisationCode": "266312",
                "card": {
                    "bin": "43302649",
                    "cardNumber": "************4675",
                    "countryCode": "BE",
                    "expiryDate": "0129"
                },
                "fraudResults": {
                    "avsResult": "U",
                    "cvvResult": "M",
                    "fraudServiceResult": "no-advice"
                },
                "paymentAccountReference": "1GBKKHSLY5BDZLZ00G1PL4LTI5ZJ6",
                "paymentProductId": 1,
                "schemeReferenceData": "396621428891",
                "threeDSecureResults": {
                    "acsTransactionId": "4C644F6D-F665-4DA3-B8C2-ECC7FFAACFA8",
                    "authenticationStatus": "Y",
                    "cavv": "AAABBEg0VhI0VniQEjRWAAAAAAA=",
                    "dsTransactionId": "f25084f0-5b16-4c0a-ae5d-b24808a95e4b",
                    "eci": "5",
                    "flow": "frictionless",
                    "liability": "issuer",
                    "schemeEci": "05",
                    "version": "2.2.0"
                },
                "token": "6c29bd1f-3cb1-4617-8a46-a5693aa56ec1"
            },
            "customer": {
                "device": {
                    "ipAddressCountryCode": "99"
                }
            },
            "paymentMethod": "card",
            "references": {
                "merchantReference": "dfed9f20-105f-432b-88b2-1bdebb354c20"
            }
        },
        "status": "CAPTURED",
        "statusOutput": {
            "isAuthorized": false,
            "isCancellable": false,
            "isRefundable": true,
            "statusCategory": "COMPLETED",
            "statusCode": 9
        }
    }
}';
        $data = new PaymentData();
        $data->fromJsonResponse(json_decode($body, true, flags: JSON_THROW_ON_ERROR));

        $this->assertSame('4361536049_0', $data->getId());
        $result = $data->getResult();
        $this->assertSame(9, $result->getCode());
        $this->assertFalse($result->isPending());

        // Make sure it also works for whole responses
        $output = Tools::obfuscatePaymentData(json_decode($body, true));
        $this->assertSame('433026**', $output['payment']['paymentOutput']['cardPaymentMethodSpecificOutput']['card']['bin']);
    }

    public function testExtractCheckoutIdFromPspDataReturnsHostedCheckoutId()
    {
        $result = Utils::extractCheckoutIdFromPspData('payone?RETURNMAC=abc123&hostedCheckoutId=checkout456');
        $this->assertEquals('checkout456', $result);
    }

    public function testExtractCheckoutIdFromPspDataWithMissing()
    {
        $result = Utils::extractCheckoutIdFromPspData('payone');
        $this->assertFalse($result);
    }
}
