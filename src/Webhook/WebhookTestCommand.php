<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Webhook;

use Dbp\Relay\MonoConnectorPayoneBundle\Config\ConfigurationService;
use Dbp\Relay\MonoConnectorPayoneBundle\Service\PayoneService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class WebhookTestCommand extends Command
{
    /**
     * @var ConfigurationService
     */
    private $config;
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        ConfigurationService $config,
        RouterInterface $router,
        private PayoneService $payoneService
    ) {
        parent::__construct();

        $this->config = $config;
        $this->router = $router;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('dbp:relay:mono-connector-payone:webhook-test');
        $this->setAliases(['dbp:relay-mono-connector-payone:webhook-test']);
        $this
            ->setDescription('Webhook test command')
            ->addArgument('contract-id', InputArgument::OPTIONAL, 'The contract ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $contractId = $input->getArgument('contract-id');

        $contract = null;
        if ($contractId !== null) {
            $contract = $this->config->getPaymentContractByIdentifier($contractId);
        }

        if ($contract === null) {
            $output->writeln("Pass one of the following contract IDs:\n");
            foreach ($this->config->getPaymentContracts() as $c) {
                $output->writeln($c->getIdentifier());
            }
        } else {
            // Show the user the URL which they need to use for registering a webhook
            $webhookUrl = $this->router->generate(
                'dbp_relay_mono_connector_payone_bundle_webhook',
                ['contract' => $contract->getIdentifier()],
                UrlGeneratorInterface::ABSOLUTE_URL);

            $api = $this->payoneService->getApiByContract($contract->getIdentifier(), null);
            $api->triggerTestWebhook($webhookUrl);
        }

        return Command::SUCCESS;
    }
}
