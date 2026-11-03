<?php

declare(strict_types=1);

namespace Yuga\Queue\Transport;

use Yuga\Queue\Payload\JobEnvelope;

final class FailedJob
{
    public function __construct(
        public readonly string $failureId,
        public readonly JobEnvelope $envelope,
        public readonly string $queue,
        public readonly int $attempts,
        public readonly string $reason,
        public readonly int $failedAt
    ) {}
}
