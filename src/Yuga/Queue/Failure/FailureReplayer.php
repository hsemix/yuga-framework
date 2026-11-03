<?php

declare(strict_types=1);

namespace Yuga\Queue\Failure;

use RuntimeException;
use Throwable;
use Yuga\Queue\Contracts\QueuePublisherInterface;
use Yuga\Queue\Contracts\ReplayableFailureStoreInterface;

final class FailureReplayer
{
    public function __construct(
        private readonly ReplayableFailureStoreInterface $store,
        private readonly QueuePublisherInterface $publisher,
        private readonly string $source
    ) {}

    public function replay(
        string $failureId,
        int $delaySeconds = 0
    ): string {
        if ($delaySeconds < 0) {
            throw new \InvalidArgumentException(
                'Replay delay must not be negative.'
            );
        }

        $claim = $this->store->claimReplay(
            $failureId,
            $this->source
        );

        if ($claim === null) {
            throw new RuntimeException(
                'Failure not found for this source, already claimed, '
                    . 'or already replayed.'
            );
        }

        try {
            $this->publisher->publish(
                envelope: $claim->failure->envelope,
                queue: $claim->failure->destination,
                delaySeconds: $delaySeconds
            );

            if (!$this->store->markReplayed($claim)) {
                throw new RuntimeException(
                    'Publication returned successfully, but replay completion '
                        . 'could not be recorded.'
                );
            }
        } catch (Throwable $exception) {
            // Never automatically release an uncertain publication.
            throw new RuntimeException(
                'Replay did not finish cleanly. The failure remains claimed; '
                    . 'verify delivery before attempting recovery.',
                previous: $exception
            );
        }

        return $claim->failure->envelope->id;
    }
}
