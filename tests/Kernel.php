<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayunityBundle\Tests;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Dbp\Relay\CoreBundle\DbpRelayCoreBundle;
use Dbp\Relay\MonoBundle\DbpRelayMonoBundle;
use Dbp\Relay\MonoConnectorPayunityBundle\DbpRelayMonoConnectorPayunityBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SecurityBundle();
        yield new TwigBundle();
        yield new NelmioCorsBundle();
        yield new MonologBundle();
        yield new DoctrineBundle();
        yield new DoctrineMigrationsBundle();
        yield new ApiPlatformBundle();
        yield new DbpRelayMonoBundle();
        yield new DbpRelayMonoConnectorPayunityBundle();
        yield new DbpRelayCoreBundle();
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import('@DbpRelayCoreBundle/Resources/config/routing.yaml');
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader)
    {
        $container->import('@DbpRelayCoreBundle/Resources/config/services_test.yaml');
        $container->extension('framework', [
            'test' => true,
            'secret' => '',
            'annotations' => false,
        ]);

        $container->extension('dbp_relay_mono', [
            'database_url' => 'mysql://dummy:dummy@dummy?serverVersion=mariadb-10.3.30',
            'cleanup' => [
                [
                    'payment_status' => 'ada',
                    'timeout_before' => '123',
                ],
            ],
            'payment_session_timeout' => 1234,
            'payment_types' => [
                [
                    'service' => 'bla',
                    'payment_contracts' => [
                        [
                            'service' => 'bla',
                            'payment_methods' => [
                                [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $container->extension('dbp_relay_mono_connector_payunity', [
            'database_url' => 'mysql://dummy:dummy@dummy?serverVersion=mariadb-10.3.30',
            'payment_contracts' => [
                'payunity_flex' => [
                    'payment_methods_to_widgets' => [
                        'foobar' => [],
                    ],
                ],
            ],
        ]);
    }
}
