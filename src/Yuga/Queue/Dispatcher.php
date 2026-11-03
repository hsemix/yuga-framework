<?php

declare(strict_types=1);

namespace Yuga\Queue;

use InvalidArgumentException;
use Yuga\Interfaces\Queue\ShouldQueueInterface;
use Yuga\Queue\Jobs\ClosureJob;
use Yuga\Queue\Payload\CodecRegistry;
use Yuga\Queue\Payload\JobEnvelope;

final class Dispatcher
{
    public function __construct(
        private readonly QueueManager $manager,
        private readonly CodecRegistry $codecs,
    ) {}

    public function dispatch(object $job): mixed
    {
        if (!$job instanceof ShouldQueueInterface) {
            return $this->dispatchNow($job);
        }

        // Read public properties only.
        $defaults = get_object_vars($job);

        return $this->enqueue(
            job: $job,
            connection: $defaults['connection'] ?? null,
            queue: $defaults['queue'] ?? 'default',
            delaySeconds: $defaults['delay'] ?? 0
        );
    }

    public function dispatchNow(object $job): mixed
    {
        if (!is_callable([$job, 'run'])) {
            throw new InvalidArgumentException(
                'A PHP job must have a public run() method.'
            );
        }

        return app()->call([$job, 'run']);
    }

    /**
     * Explicitly enqueue an object, regardless of its marker interface.
     */
    public function enqueue(
        object $job,
        ?string $connection = null,
        string $queue = 'default',
        int $delaySeconds = 0
    ): JobEnvelope {
        if (!is_callable([$job, 'run'])) {
            throw new InvalidArgumentException(
                'A PHP job must have a public run() method.'
            );
        }

        if ($job instanceof ClosureJob) {
            return $this->publish(
                type: 'yuga.closure.v1',
                payload: $job,
                connection: $connection,
                queue: $queue,
                delaySeconds: $delaySeconds,
                encoding: 'opis-closure-v4'
            );
        }

        return $this->publish(
            type: $job::class,
            payload: $job,
            connection: $connection,
            queue: $queue,
            delaySeconds: $delaySeconds,
            encoding: 'php'
        );
    }

    public function prepare(object $job): PendingDispatch
    {
        return new PendingDispatch($this, $job);
    }

    public function publish(
        string $type,
        mixed $payload,
        ?string $connection = null,
        string $queue = 'default',
        int $delaySeconds = 0,
        string $encoding = 'json'
    ): JobEnvelope {
        if (trim($queue) === '' || $delaySeconds < 0) {
            throw new InvalidArgumentException(
                'Queue must not be empty and delay must not be negative.'
            );
        }

        $envelope = JobEnvelope::create(
            $type,
            $payload,
            $this->codecs->get($encoding)
        );

        $this->manager->connection($connection)->publish(
            $envelope,
            $queue,
            $delaySeconds
        );

        return $envelope;
    }
}
