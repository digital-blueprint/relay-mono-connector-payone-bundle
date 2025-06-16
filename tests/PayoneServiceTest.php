<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PayoneServiceTest extends KernelTestCase
{
    public function testKernel()
    {
        $this->assertNotNull(self::bootKernel());
    }
}
