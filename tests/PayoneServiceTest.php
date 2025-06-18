<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Tests;

use Dbp\Relay\MonoConnectorPayoneBundle\Payone\Tools;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PayoneServiceTest extends KernelTestCase
{
    public function testKernel()
    {
        $this->assertNotNull(self::bootKernel());
    }

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
}
