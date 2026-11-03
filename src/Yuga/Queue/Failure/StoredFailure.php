<?php

declare(strict_types=1);

namespace Yuga\Queue\Failure;

use Yuga\Queue\Payload\JobEnvelope;

final class StoredFailure
{
    public function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly string $deliveryId,
        public readonly string $destination,
        public readonly JobEnvelope $envelope,
        public readonly int $attempts,
        public readonly string $reason,
        public readonly int $failedAt
    ) {}
}
