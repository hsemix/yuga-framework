<?php

declare(strict_types=1);

namespace Yuga\Queue;

use Yuga\Queue\Contracts\QueueTransportInterface;
use Yuga\Queue\Contracts\RenewableTransportInterface;
use Yuga\Queue\Exceptions\ReservationLostException;
use Yuga\Queue\Exceptions\UnsupportedCapabilityException;
use Yuga\Queue\Transport\JobReservation;

final class JobContext
{
    public readonly string $jobId;
    public readonly int $attempt;

    public function __construct(
        private readonly QueueTransportInterface $transport,
        private readonly JobReservation $reservation
    ) {
        $this->jobId = $reservation->envelope->id;
        $this->attempt = $reservation->attempt;
    }

    public function renew(int $additionalSeconds): void
    {
        if (!$this->transport instanceof RenewableTransportInterface) {
            throw new UnsupportedCapabilityException(
                'This transport does not support reservation renewal.'
            );
        }

        if (!$this->transport->renew(
            $this->reservation,
            $additionalSeconds
        )) {
            throw new ReservationLostException(
                "Reservation ownership was lost for job [{$this->jobId}]."
            );
        }
    }
}
