<?php

declare(strict_types=1);

namespace Yuga\Queue\Contracts;

use Yuga\Queue\Transport\FailedJob;

interface FailedJobTransportInterface
{
    /**
     * Return failures, newest first.
     *
     * @return list<FailedJob>
     */
    public function failedJobs(
        ?string $queue = null,
        int $limit = 50,
        int $offset = 0
    ): array;

    public function findFailed(string $failureId): ?FailedJob;

    /**
     * Atomically return a failed job to normal delivery.
     *
     * Preserve its envelope identity and body.
     * Reset attempts and clear its failure reason.
     * Return false if it is no longer failed or does not exist.
     */
    public function retryFailed(
        string $failureId,
        int $delaySeconds = 0
    ): bool;

    /**
     * Delete only the specified failed job.
     */
    public function forgetFailed(string $failureId): bool;
}
