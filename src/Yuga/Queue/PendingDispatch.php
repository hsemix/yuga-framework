<?php

declare(strict_types=1);

namespace Yuga\Queue;

use LogicException;
use Yuga\Queue\Payload\JobEnvelope;

final class PendingDispatch
{
    private ?string $connection = null;
    private string $queue = 'default';
    private int $delaySeconds = 0;
    private bool $sent = false;

    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly object $job
    ) {}

    public function onConnection(string $connection): self
    {
        $this->ensureNotSent();
        $this->connection = $connection;

        return $this;
    }

    public function onQueue(string $queue): self
    {
        $this->ensureNotSent();
        $this->queue = $queue;

        return $this;
    }

    public function delay(int $seconds): self
    {
        $this->ensureNotSent();
        $this->delaySeconds = $seconds;

        return $this;
    }

    public function send(): JobEnvelope
    {
        $this->ensureNotSent();

        $envelope = $this->dispatcher->enqueue(
            $this->job,
            $this->connection,
            $this->queue,
            $this->delaySeconds
        );

        $this->sent = true;

        return $envelope;
    }

    private function ensureNotSent(): void
    {
        if ($this->sent) {
            throw new LogicException(
                'This pending job has already been published.'
            );
        }
    }
}
