<?php

declare(strict_types=1);

namespace Yuga\Queue\Contracts;

use Yuga\Queue\Payload\JobEnvelope;
use Yuga\Queue\Transport\JobReservation;

interface QueueTransportInterface extends QueuePublisherInterface
{

    /**
     * Atomically reserve one available job.
     *
     * Return null when no job is currently available.
     * Increment the attempt count when reservation succeeds.
     *
     * After the reservation expires, an unacknowledged job must
     * become eligible for recovery.
     */
    public function reserve(
        string $queue = 'default',
        int $reservationSeconds = 60
    ): ?JobReservation;

    /**
     * Complete a job owned by this reservation.
     *
     * Return false if ownership has expired or been lost.
     */
    public function acknowledge(JobReservation $reservation): bool;

    /**
     * Return an owned job to the queue for another attempt.
     *
     * Preserve its ID and attempt count.
     * Return false if ownership has expired or been lost.
     * Throw if the requested delay is unsupported.
     */
    public function release(
        JobReservation $reservation,
        int $delaySeconds = 0
    ): bool;

    /**
     * Mark an owned job as terminally failed.
     *
     * Remove it from normal delivery and retain its envelope,
     * attempt count, and failure reason for inspection.
     *
     * Return false if ownership has expired or been lost.
     */
    public function fail(
        JobReservation $reservation,
        string $reason
    ): bool;
}
