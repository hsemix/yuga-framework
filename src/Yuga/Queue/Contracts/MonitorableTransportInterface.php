<?php

declare(strict_types=1);

namespace Yuga\Queue\Contracts;

interface MonitorableTransportInterface
{
    /**
     * Return a snapshot of stored delivery states.
     *
     * Counts may change immediately after this call.
     *
     * @return array{
     *     ready: int,
     *     delayed: int,
     *     reserved: int,
     *     expired: int,
     *     failed: int,
     *     completed: int,
     *     total: int
     * }
     */
    public function statistics(?string $queue = null): array;
}