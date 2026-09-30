<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Tests;

use Dbp\Relay\CoreBundle\TestUtils\CoreTestKernelTrait;
use Dbp\Relay\MonoBundle\DbpRelayMonoBundle;
use Dbp\Relay\MonoConnectorPayoneBundle\DbpRelayMonoConnectorPayoneBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use CoreTestKernelTrait;

    protected function registerAdditionalBundles(): iterable
    {
        yield new DoctrineBundle();
        yield new DoctrineMigrationsBundle();
        yield new DbpRelayMonoBundle();
        yield new DbpRelayMonoConnectorPayoneBundle();
    }

    protected function configureAdditionalContainer(ContainerConfigurator $container): void
    {
        $container->extension('dbp_relay_mono', [
            'database_url' => 'sqlite:///:memory:',
            'payment_types' => [
                'something' => [
                    'backend_type' => 'bla',
                    'payment_methods' => [
                    ],
                ],
            ],
        ]);

        $container->extension('dbp_relay_mono_connector_payone', [
            'database_url' => 'sqlite:///:memory:',
            'payment_contracts' => [
                'payone_hosted_payment_page' => [
                    'api_url' => '',
                    'merchant_id' => '',
                    'api_key_id' => '',
                    'api_secret' => '',
                    'webhook_id' => '',
                    'webhook_secret' => '',
                    'payment_methods' => [
                        'foobar' => [],
                    ],
                ],
            ],
        ]);
    }
}
