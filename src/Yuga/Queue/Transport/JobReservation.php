<?php

declare(strict_types=1);

namespace Yuga\Queue\Transport;

use InvalidArgumentException;
use Yuga\Queue\Payload\JobEnvelope;

final class JobReservation
{
    public function __construct(
        public readonly JobEnvelope $envelope,
        public readonly mixed $receipt,
        public readonly int $attempt
    ) {
        if ($attempt < 1) {
            throw new InvalidArgumentException(
                'A reserved job must have an attempt number of at least 1.'
            );
        }
    }
}
