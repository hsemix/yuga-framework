<?php

declare(strict_types=1);

namespace Yuga\Queue\Transport;

use InvalidArgumentException;
use Spiral\RoadRunner\Jobs\Jobs;
use Throwable;
use Yuga\Queue\Contracts\QueuePublisherInterface;
use Yuga\Queue\Exceptions\TransportException;
use Yuga\Queue\Payload\JobEnvelope;

final class RoadRunnerPublisher implements QueuePublisherInterface
{
    /**
     * @param array<string, string> $pipelines
     *        Yuga queue name => RoadRunner pipeline name.
     */
    public function __construct(
        private readonly Jobs $jobs,
        private readonly array $pipelines
    ) {}

    public function publish(
        JobEnvelope $envelope,
        string $queue = 'default',
        int $delaySeconds = 0
    ): void {
        if (trim($queue) === '') {
            throw new InvalidArgumentException(
                'Queue name must not be empty.'
            );
        }

        if ($delaySeconds < 0) {
            throw new InvalidArgumentException(
                'Delay must not be negative.'
            );
        }

        $pipeline = $this->pipelines[$queue] ?? null;

        if (!is_string($pipeline) || trim($pipeline) === '') {
            throw new InvalidArgumentException(
                "No RoadRunner pipeline configured for queue [{$queue}]."
            );
        }

        try {
            $destination = $this->jobs->connect($pipeline);

            $task = $destination
                ->create($envelope->type, $envelope->body)
                ->withHeader('yuga-version', (string) $envelope->version)
                ->withHeader('yuga-id', $envelope->id)
                ->withHeader('yuga-type', $envelope->type)
                ->withHeader('yuga-encoding', $envelope->encoding)
                ->withHeader('yuga-queue', $queue)
                ->withHeader('yuga-attempt', '1')
                ->withDelay($delaySeconds);

            $destination->dispatch($task);
        } catch (Throwable $exception) {
            throw new TransportException(
                'RoadRunner job publication failed.',
                previous: $exception
            );
        }
    }
}
