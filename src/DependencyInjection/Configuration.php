<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dbp_relay_mono_connector_payone');

        $treeBuilder
            ->getRootNode()
                ->children()
                    ->scalarNode('database_url')
                        ->info('The database DSN')
                        ->isRequired()
                    ->end()
                    ->arrayNode('payment_contracts')
                        ->info('Zero or more payment contracts. The "payment_contract" can be referenced in the "mono" config.')
                        ->useAttributeAsKey('payment_contract')
                        ->defaultValue([])
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('api_url')
                                    ->info('The PAYONE API endpoint.')
                                    ->example('https://payment.preprod.payone.com/')
                                    ->isRequired()
                                ->end()
                                ->scalarNode('merchant_id')
                                    ->info('The merchantId (PSPID) provided by PAYONE')
                                    ->isRequired()
                                ->end()
                                ->scalarNode('api_key_id')
                                    ->info('The API key ID provided by PAYONE')
                                    ->isRequired()
                                ->end()
                                ->scalarNode('api_secret')
                                    ->info('The Secret API key provided by PAYONE')
                                    ->isRequired()
                                ->end()
                                ->scalarNode('webhook_id')
                                    ->info('The Webhook ID provided by PAYONE')
                                    ->isRequired()
                                ->end()
                                ->scalarNode('webhook_secret')
                                    ->info('The Secret webhook key provided by PAYONE')
                                    ->isRequired()
                                ->end()
                                ->arrayNode('payment_methods')
                                    ->info('Zero or more payment methods. The "payment_method" can be referenced in the "mono" config.')
                                    ->useAttributeAsKey('payment_method')
                                    ->defaultValue([])
                                    ->arrayPrototype()
                                        ->children()
                                            ->arrayNode('products')
                                                ->info('A list of payment product labels. See Payment methods in the PAYONE portal')
                                                ->example(['MasterCard', 'VISA'])
                                                ->defaultValue([])
                                                ->scalarPrototype()
                                                ->end()
                                            ->end()
                                            ->scalarNode('template_variant')
                                                ->info('The variant/template variant name used (for theming/branding of the payment website)')
                                                ->example('SimplifiedCustomPaymentPage')
                                            ->end()
                                        ->end()

                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
