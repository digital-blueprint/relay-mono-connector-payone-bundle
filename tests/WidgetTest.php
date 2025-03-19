<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Tests;

use Dbp\Relay\MonoConnectorPayoneBundle\Controller\Widget;
use PHPUnit\Framework\TestCase;

class WidgetTest extends TestCase
{
    public function testTemplate(): void
    {
        $this->assertSame('index.html.twig', Widget::getTemplateForBrands('foo', ['VISA']));
        $this->assertSame('applepay.html.twig', Widget::getTemplateForBrands('foo', ['APPLEPAY']));

        $this->expectExceptionMessage('foobar');
        Widget::getTemplateForBrands('foobar', ['APPLEPAY', 'VISA']);
    }
}
