<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Payone;

/**
 * https://developer.payone.com/de/integration/api-developer-guide/statuses.
 */
class ResultStatusCode
{
    /**
     * @var int
     */
    private $code;
    /**
     * @var string
     */
    private $description;

    public function __construct(int $code, string $description = '')
    {
        $this->code = $code;
        $this->description = $description;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param array<int,int> $allowedStatusCodes - allowed status codes
     */
    private function matches(array $allowedStatusCodes): bool
    {
        return in_array($this->code, $allowedStatusCodes, true);
    }

    /**
     * Result codes for successfully processed transactions.
     */
    public function isSuccessfullyProcessed(): bool
    {
        return $this->matches([9]);
    }

    /**
     * Result codes for successfully processed transactions that should be manually reviewed.
     */
    public function isSuccessfullyProcessedNeedsManualReview(): bool
    {
        return false;
    }

    public function isCapturable(): bool
    {
        return $this->matches([5, 56]);
    }

    /**
     * Result codes for pending transactions.
     * These codes mean that there is an open session in the background, meaning within half an hour there will
     * be a status change, if nothing else happens, to timeout.
     */
    public function isPending(): bool
    {
        return $this->matches([5, 56, 50, 51, 55, 4, 91, 92, 99]);
    }

    /**
     * These codes describe a situation where the status of a transaction can change even after several days.
     */
    public function isPendingExtra(): bool
    {
        return false;
    }
}
