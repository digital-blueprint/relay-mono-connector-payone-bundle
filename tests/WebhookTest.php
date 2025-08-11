<?php

declare(strict_types=1);

use Dbp\Relay\MonoConnectorPayoneBundle\Webhook\WebhookRequest;
use PHPUnit\Framework\TestCase;

class WebhookTest extends TestCase
{
    public const PAYLOAD = '{
    "apiVersion": "v1",
    "created": "2025-12-24T12:00:00.0000000+02:00",
    "id": "cfe3ded2-3876-41e3-9662-91f9a1ccb6f3",
    "merchantId": "SomeMerchant",
    "type": "payment.captured",
    "payment": {
        "id": "1234567890_0",
        "paymentOutput": {
            "acquiredAmount": {
                "amount": 42,
                "currencyCode": "EUR"
            },
            "amountOfMoney": {
                "amount": 42,
                "currencyCode": "EUR"
            },
            "cardPaymentMethodSpecificOutput": {
                "acquirerInformation": {
                    "name": "SomeAcquirer"
                },
                "authorisationCode": "123456",
                "card": {
                    "bin": "123456**",
                    "cardNumber": "************1337",
                    "countryCode": "DE",
                    "expiryDate": "**99"
                },
                "fraudResults": {
                    "avsResult": "U",
                    "cvvResult": "0",
                    "fraudServiceResult": "no-advice"
                },
                "paymentProductId": 3,
                "schemeReferenceData": "DUMMY",
                "threeDSecureResults": {
                    "acsTransactionId": "798e09bf-9b8d-436b-a45d-494187d02923",
                    "authenticationStatus": "Y",
                    "cavv": "nope",
                    "dsTransactionId": "9c78675c-3248-415b-8d39-60fca3412dec",
                    "eci": "5",
                    "flow": "challenge",
                    "liability": "issuer",
                    "schemeEci": "02",
                    "version": "2.2.0"
                }
            },
            "customer": {
                "device": {
                    "ipAddressCountryCode": "99"
                }
            },
            "paymentMethod": "card",
            "references": {
                "merchantReference": "d47833a6-6f54-4a72-93a3-9e3275ca63f1"
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

    public function testWebhook()
    {
        $keyId = 'test-key-id';
        $secretKey = 'test-secret-key-12345';
        $request = WebhookRequest::createTestRequest($keyId, $secretKey, self::PAYLOAD);

        $webhookRequest = WebhookRequest::validateRequest($keyId, $secretKey, $request);
        $this->assertSame(WebhookRequest::TYPE_CAPTURED, $webhookRequest->getType());
        $this->assertSame('d47833a6-6f54-4a72-93a3-9e3275ca63f1', $webhookRequest->getIdentifier());
    }

    public function testWebhookWrongKeyId()
    {
        $keyId = 'test-key-id';
        $secretKey = 'test-secret-key-12345';
        $request = WebhookRequest::createTestRequest($keyId, $secretKey, self::PAYLOAD);

        $this->expectException(Exception::class);
        WebhookRequest::validateRequest('wrongid', $secretKey, $request);
    }

    public function testWebhookWrongKeySecret()
    {
        $keyId = 'test-key-id';
        $secretKey = 'test-secret-key-12345';
        $request = WebhookRequest::createTestRequest($keyId, $secretKey, self::PAYLOAD);

        $this->expectException(Exception::class);
        WebhookRequest::validateRequest($keyId, 'nope', $request);
    }

    public function testMinimal()
    {
        $keyId = 'test-key-id';
        $secretKey = 'test-secret-key-12345';
        $request = WebhookRequest::createTestRequest($keyId, $secretKey, '{"apiVersion": "v1", "type": "test", "payment": {"paymentOutput": {"references": {"merchantReference": "something"}}}}');

        WebhookRequest::validateRequest($keyId, $secretKey, $request);
        $this->expectNotToPerformAssertions();
    }
}
