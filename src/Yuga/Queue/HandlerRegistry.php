<?php

declare(strict_types=1);

namespace Yuga\Queue;

use Closure;
use InvalidArgumentException;
use UnexpectedValueException;
use Yuga\Queue\Payload\JobEnvelope;

final class HandlerRegistry
{
    /** @var array<string, Closure> */
    private array $handlers = [];

    private readonly Closure $objectExecutor;

    public function __construct(callable $objectExecutor)
    {
        $this->objectExecutor = Closure::fromCallable($objectExecutor);
    }

    public function register(string $type, callable $handler): self
    {
        if (trim($type) === '') {
            throw new InvalidArgumentException(
                'Job type must not be empty.'
            );
        }

        if (isset($this->handlers[$type])) {
            throw new InvalidArgumentException(
                "Handler [{$type}] is already registered."
            );
        }

        $this->handlers[$type] = Closure::fromCallable($handler);

        return $this;
    }

    public function execute(JobEnvelope $envelope, mixed $payload, ?JobContext $context = null): void
    {
        // Explicit handlers work with any registered encoding.
        if (isset($this->handlers[$envelope->type])) {
            ($this->handlers[$envelope->type])($payload, $envelope, $context);

            return;
        }

        if ($envelope->encoding === 'php') {
            if (
                !is_object($payload)
                || $payload::class !== $envelope->type
                || !is_callable([$payload, 'run'])
            ) {
                throw new UnexpectedValueException(
                    'PHP job type must match an object with a public run() method.'
                );
            }

            ($this->objectExecutor)($payload, $context);

            return;
        }

        throw new UnexpectedValueException(
            "No handler registered for [{$envelope->type}]."
        );
    }
}
