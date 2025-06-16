<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Tests;

use Dbp\Relay\MonoBundle\Persistence\PaymentPersistence;
use Dbp\Relay\MonoConnectorPayoneBundle\Payone\Checkout;
use Dbp\Relay\MonoConnectorPayoneBundle\Persistence\PaymentDataService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PaymentDataServiceTest extends KernelTestCase
{
    private EntityManager $em;
    private PaymentDataService $service;

    public function setUp(): void
    {
        $container = $this->getContainer();
        $this->em = $container->get('doctrine')->getManager('dbp_relay_mono_connector_payone_bundle');
        $this->em->clear();
        $metaData = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->updateSchema($metaData);
        $this->service = new PaymentDataService($this->em);
    }

    public function tearDown(): void
    {
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropDatabase();
    }

    public function testCheckConnection(): void
    {
        $this->service->checkConnection();
        $this->assertTrue(true);
    }

    public function testCreatePaymentData(): void
    {
        $checkout = new Checkout();
        $checkout->fromJsonResponse([
            'hostedCheckoutId' => 'checkout-id',
            'redirectUrl' => 'https://redirect-url',
        ]);
        $payment = new PaymentPersistence();
        $payment->setIdentifier('foo');
        $this->service->createPaymentData('somecontract', 'somemethod', $payment, $checkout);

        $paymentData = $this->service->getByPaymentIdentifier('foo');
        $this->assertSame(1, $paymentData->getIdentifier());
        $paymentData->setIdentifier($paymentData->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $paymentData->getCreatedAt());
        $this->assertSame('foo', $paymentData->getPaymentIdentifier());
        $this->assertSame('checkout-id', $paymentData->getPspIdentifier());
        $this->assertSame('somecontract', $paymentData->getPspContract());
        $this->assertSame('somemethod', $paymentData->getPspMethod());

        $paymentData = $this->service->getByCheckoutId('checkout-id');
        $this->assertSame('foo', $paymentData->getPaymentIdentifier());

        $this->service->cleanupByPaymentIdentifier('foo');
        $paymentData = $this->service->getByPaymentIdentifier('foo');
        $this->assertNull($paymentData);
    }

    public function testCleanupByPaymentIdentifier(): void
    {
        $this->service->cleanupByPaymentIdentifier('something');
        $this->assertTrue(true);
    }

    public function testGetByCheckoutId(): void
    {
        $this->assertNull($this->service->getByCheckoutId('something'));
    }

    public function testGetByPaymentIdentifier(): void
    {
        $this->assertNull($this->service->getByPaymentIdentifier('something'));
    }
}
