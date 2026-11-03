<?php

declare(strict_types=1);

namespace Yuga\Queue\Contracts;

use Yuga\Queue\Transport\JobReservation;

interface RenewableTransportInterface
{
    /**
     * Extend an owned, unexpired reservation by additional seconds.
     *
     * Return false if ownership has expired or been lost.
     * Throw on a backend error.
     */
    public function renew(
        JobReservation $reservation,
        int $additionalSeconds
    ): bool;
}
