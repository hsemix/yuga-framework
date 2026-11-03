<?php

declare(strict_types=1);

namespace Yuga\Queue\Contracts;

use Yuga\Queue\Failure\ReplayClaim;

interface ReplayableFailureStoreInterface extends
    InspectableFailureStoreInterface
{
    /**
     * Atomically claim a pending failure belonging to this source.
     *
     * Return null if missing, already claimed, or already replayed.
     */
    public function claimReplay(
        string $failureId,
        string $source
    ): ?ReplayClaim;

    public function markReplayed(ReplayClaim $claim): bool;
}
