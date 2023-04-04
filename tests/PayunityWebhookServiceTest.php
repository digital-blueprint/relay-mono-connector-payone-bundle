<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayunityBundle\Tests;

use Dbp\Relay\MonoConnectorPayunityBundle\Entity\PaymentContract;
use Dbp\Relay\MonoConnectorPayunityBundle\Service\PayunityWebhookService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class PayunityWebhookServiceTest extends TestCase
{
    // taken from https://www.payunity.com/tutorials/webhooks/integration-guide
    private const EXAMPLE_PAYLOAD = '
    {
   "type":"PAYMENT",
   "payload":{
      "id":"8a829449515d198b01517d5601df5584",
      "paymentType":"PA",
      "paymentBrand":"VISA",
      "amount":"92.00",
      "currency":"EUR",
      "presentationAmount":"92.00",
      "presentationCurrency":"EUR",
      "descriptor":"3017.7139.1650 OPP_Channel ",
      "result":{
         "code":"000.100.110",
         "description":"Request successfully processed in \'Merchant in Integrator Test Mode\'"
      },
      "authentication":{
         "entityId":"8a8294185282b95b01528382b4940245"
      },
      "card":{
         "bin":"420000",
         "last4Digits":"0000",
         "holder":"Jane Jones",
         "expiryMonth":"05",
         "expiryYear":"2018"
      },
      "customer":{
         "givenName":"Jones",
         "surname":"Jane",
         "merchantCustomerId":"jjones",
         "sex":"F",
         "email":"jane@jones.com"
      },
      "customParameters":{
         "SHOPPER_promoCode":"AT052"
      },
      "risk":{
         "score":"0"
      },
      "buildNumber":"ec3c704170e54f6d7cf86c6f1969b20f6d855ce5@2015-12-01 12:20:39 +0000",
      "timestamp":"2015-12-07 16:46:07+0000",
      "ndc":"8a8294174b7ecb28014b9699220015ca_66b12f658442479c8ca66166c4999e78"
   }
}';

    public function testDecrypt()
    {
        $secret = 'foobar';
        $ivLen = \openssl_cipher_iv_length('aes-256-gcm');
        $iv = \openssl_random_pseudo_bytes($ivLen);
        $encrypted = \openssl_encrypt(self::EXAMPLE_PAYLOAD, 'aes-256-gcm', $secret, OPENSSL_RAW_DATA, $iv, $tag);
        $this->assertNotFalse($encrypted);

        $service = new PayunityWebhookService();
        $request = new Request([], [], [], [], [], [], bin2hex($encrypted));
        $request->headers->set('X-Initialization-Vector', bin2hex($iv));
        $request->headers->set('X-Authentication-Tag', bin2hex($tag));

        $contract = new PaymentContract();
        $contract->setWebhookSecret(bin2hex($secret));

        $result = $service->decryptRequest($contract, $request);
        $this->assertSame('PAYMENT', $result->getType());
        $pspDataArray = $result->getPayload();
        $this->assertSame('8a8294174b7ecb28014b9699220015ca_66b12f658442479c8ca66166c4999e78', $pspDataArray['ndc']);
    }
}
