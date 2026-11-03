<?php

declare(strict_types=1);

namespace Yuga\Queue;

use Yuga\Queue\Payload\CodecRegistry;
use Yuga\Queue\Payload\JobEnvelope;

final class JobExecutor
{
    public function __construct(
        private readonly CodecRegistry $codecs,
        private readonly HandlerRegistry $handlers
    ) {
    }

    public function decode(JobEnvelope $envelope): mixed
    {
        return $envelope->decode(
            $this->codecs->get($envelope->encoding)
        );
    }

    public function execute(
        JobEnvelope $envelope,
        mixed $payload,
        ?JobContext $context = null
    ): void {
        $this->handlers->execute(
            $envelope,
            $payload,
            $context
        );
    }
}
