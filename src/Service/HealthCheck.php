<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Service;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;
use Dbp\Relay\MonoConnectorPayoneBundle\Persistence\PaymentDataService;

class HealthCheck implements CheckInterface
{
    /**
     * @var PayoneService
     */
    private $payoneService;
    /**
     * @var PaymentDataService
     */
    private $dataService;

    public function __construct(PayoneService $payoneService, PaymentDataService $dataService)
    {
        $this->payoneService = $payoneService;
        $this->dataService = $dataService;
    }

    public function getName(): string
    {
        return 'mono-connector-payone';
    }

    /**
     * @param array<mixed> $args
     */
    private function checkMethod(string $description, callable $func, array $args = []): CheckResult
    {
        $result = new CheckResult($description);
        try {
            $func(...$args);
        } catch (\Throwable $e) {
            $result->set(CheckResult::STATUS_FAILURE, $e->getMessage(), ['exception' => $e]);

            return $result;
        }
        $result->set(CheckResult::STATUS_SUCCESS);

        return $result;
    }

    public function check(CheckOptions $options): array
    {
        $results = [];

        $results[] = $this->checkMethod('Check if we can connect to the DB', [$this->dataService, 'checkConnection']);

        foreach ($this->payoneService->getContracts() as $contract) {
            $id = $contract->getIdentifier();
            $results[] = $this->checkMethod('Check if we can connect to the PAYONE API ('.$id.')', [$this->payoneService, 'checkConnection'], [$id]);
        }

        return $results;
    }
}
