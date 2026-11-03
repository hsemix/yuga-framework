<?php

declare(strict_types=1);

namespace Yuga\Queue\Contracts;

use Yuga\Queue\Payload\JobEnvelope;

interface QueuePublisherInterface
{
    /**
     * Publish an envelope.
     *
     * Preserve its ID and raw body exactly.
     * Throw if publishing fails or the requested delay is unsupported.
     */
    public function publish(
        JobEnvelope $envelope,
        string $queue = 'default',
        int $delaySeconds = 0
    ): void;
}
