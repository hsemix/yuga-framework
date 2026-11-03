<?php

declare(strict_types=1);

namespace Yuga\Queue\Contracts;

use Yuga\Queue\Payload\JobEnvelope;

interface FailureStoreInterface
{
    /**
     * Persist a terminal failure and return its failure ID.
     *
     * Repeated calls with the same source and delivery ID must
     * return the same ID without creating duplicate records.
     *
     * Throw if persistence fails.
     */
    public function record(
        string $source,
        string $deliveryId,
        string $queue,
        JobEnvelope $envelope,
        int $attempts,
        string $reason
    ): string;
}
